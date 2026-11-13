<?php

namespace App\Services;

use App\Models\BomItem;
use App\Models\Material;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanMaterial;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MaterialRequirementService
{
    private const SCALE = 100;

    private const MAX_QUANTITY_UNITS = 99_999_999_999_999;

    public function createPlannedPlan(array $attributes, ?string $planNumber = null): ProductionPlan
    {
        return DB::transaction(function () use ($attributes, $planNumber): ProductionPlan {
            $product = Product::query()
                ->whereKey($attributes['product_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'product_id' => 'Produk tidak tersedia atau sudah nonaktif.',
                ]);
            }

            $bomItems = BomItem::query()
                ->where('product_id', $product->id)
                ->with('material')
                ->lockForUpdate()
                ->get();

            if ($bomItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'product_id' => 'Produk ini belum memiliki BOM material.',
                ]);
            }

            $plannedQuantity = (string) $attributes['planned_quantity'];
            $plannedUnits = $this->toUnits($plannedQuantity, 2);
            $plan = ProductionPlan::create([
                'plan_number' => $planNumber ?? $this->generatePlanNumber(),
                'product_id' => $product->id,
                'planned_quantity' => $plannedQuantity,
                'due_date' => $attributes['due_date'],
                'status' => ProductionPlan::STATUS_PLANNED,
            ]);

            foreach ($bomItems as $bomItem) {
                $requiredQuantity = $this->calculateRequiredQuantity(
                    $plannedUnits,
                    $this->toUnits((string) $bomItem->quantity_per_product, 2),
                );

                $plan->materials()->create([
                    'material_id' => $bomItem->material_id,
                    'required_quantity' => $requiredQuantity,
                ]);
            }

            return $plan;
        }, attempts: 3);
    }

    /**
     * @return Collection<int, array{material: Material, required_quantity: string, available_quantity: string, shortage_quantity: string, status: string, plan_count: int, plans: Collection<int, ProductionPlan>}>
     */
    public function requirementsForPlan(ProductionPlan $plan): Collection
    {
        $snapshots = $plan->materials()->with(['material.stock'])->get();
        $requiredByMaterial = $snapshots
            ->groupBy('material_id')
            ->map(fn (Collection $items): int => $items->sum(
                fn (ProductionPlanMaterial $item): int => $this->toUnits($item->required_quantity, 2),
            ));

        $materials = $snapshots
            ->map(fn (ProductionPlanMaterial $item): ?Material => $item->material)
            ->filter()
            ->keyBy('id');

        return $this->buildRequirementRows($requiredByMaterial, preloadedMaterials: $materials);
    }

    /**
     * @param Collection<int, ProductionPlan> $plans
     * @return array<int, int> Plan ID mapped to its number of insufficient materials.
     */
    public function shortageCountsForPlans(Collection $plans): array
    {
        if ($plans->isEmpty()) {
            return [];
        }

        $snapshotsByPlan = ProductionPlanMaterial::query()
            ->whereIn('production_plan_id', $plans->modelKeys())
            ->with(['material.stock'])
            ->get()
            ->groupBy('production_plan_id');
        $shortageCounts = [];

        foreach ($plans as $plan) {
            $snapshots = $snapshotsByPlan->get($plan->id, collect());
            $requiredByMaterial = $snapshots
                ->groupBy('material_id')
                ->map(fn (Collection $items): int => $items->sum(
                    fn (ProductionPlanMaterial $item): int => $this->toUnits($item->required_quantity, 2),
                ));
            $materials = $snapshots
                ->map(fn (ProductionPlanMaterial $item): ?Material => $item->material)
                ->filter()
                ->keyBy('id');

            $shortageCounts[$plan->id] = $this->buildRequirementRows(
                $requiredByMaterial,
                preloadedMaterials: $materials,
            )->where('status', 'kurang')->count();
        }

        return $shortageCounts;
    }

    /**
     * Aggregate all planned snapshots before comparing the total with each material's stock.
     *
     * @return Collection<int, array{material: Material, required_quantity: string, available_quantity: string, shortage_quantity: string, status: string}>
     */
    public function aggregatePlannedRequirements(): Collection
    {
        return $this->plannedRequirementsReport()['requirements'];
    }

    /**
     * @param array{due_date_from?: string|null, due_date_to?: string|null, product_id?: int|string|null} $filters
     * @return array{requirements: Collection<int, array{material: Material, required_quantity: string, available_quantity: string, shortage_quantity: string, status: string, plan_count: int, plans: Collection<int, ProductionPlan>}>, plan_count: int}
     */
    public function plannedRequirementsReport(array $filters = []): array
    {
        $plannedPlans = ProductionPlan::query()
            ->where('status', ProductionPlan::STATUS_PLANNED);
        $this->applyPlanFilters($plannedPlans, $filters);
        $planCount = (clone $plannedPlans)->count();

        $snapshots = ProductionPlanMaterial::query()
            ->whereHas('productionPlan', function (Builder $query) use ($filters): void {
                $query->where('status', ProductionPlan::STATUS_PLANNED);
                $this->applyPlanFilters($query, $filters);
            })
            ->with(['material.stock', 'productionPlan.product'])
            ->get();

        $requiredByMaterial = $snapshots
            ->groupBy('material_id')
            ->map(fn (Collection $items): int => $items->sum(
                fn (ProductionPlanMaterial $item): int => $this->toUnits($item->required_quantity, 2),
            ));
        $plansByMaterial = $snapshots
            ->groupBy('material_id')
            ->map(fn (Collection $items): Collection => $items
                ->map(fn (ProductionPlanMaterial $item): ?ProductionPlan => $item->productionPlan)
                ->filter()
                ->unique('id')
                ->sortBy('due_date')
                ->values());

        return [
            'requirements' => $this->buildRequirementRows($requiredByMaterial, $plansByMaterial),
            'plan_count' => $planCount,
        ];
    }

    private function generatePlanNumber(): string
    {
        return 'PP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    /**
     * @param Builder<ProductionPlan> $query
     * @param array{due_date_from?: string|null, due_date_to?: string|null, product_id?: int|string|null} $filters
     */
    private function applyPlanFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['due_date_from'])) {
            $query->whereDate('due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->whereDate('due_date', '<=', $filters['due_date_to']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }
    }

    private function calculateRequiredQuantity(int $plannedUnits, int $bomUnits): string
    {
        $maximumProduct = self::MAX_QUANTITY_UNITS * 100 + 49;

        if ($bomUnits > 0 && $plannedUnits > intdiv($maximumProduct, $bomUnits)) {
            throw ValidationException::withMessages([
                'planned_quantity' => 'Kebutuhan material melebihi kapasitas nilai yang dapat disimpan.',
            ]);
        }

        $requiredUnits = intdiv(($plannedUnits * $bomUnits) + 50, 100);

        if ($requiredUnits > self::MAX_QUANTITY_UNITS) {
            throw ValidationException::withMessages([
                'planned_quantity' => 'Kebutuhan material melebihi kapasitas nilai yang dapat disimpan.',
            ]);
        }

        return $this->fromUnits($requiredUnits);
    }

    /**
     * @param Collection<int, int> $requiredByMaterial
     * @return Collection<int, array{material: Material, required_quantity: string, available_quantity: string, shortage_quantity: string, status: string}>
     */
    private function buildRequirementRows(
        Collection $requiredByMaterial,
        ?Collection $plansByMaterial = null,
        ?Collection $preloadedMaterials = null,
    ): Collection
    {
        if ($requiredByMaterial->isEmpty()) {
            return collect();
        }

        $materials = $preloadedMaterials ?? Material::query()
            ->with('stock')
            ->whereIn('id', $requiredByMaterial->keys())
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        return $requiredByMaterial
            ->sortKeys()
            ->map(function (int $requiredUnits, int|string $materialId) use ($materials, $plansByMaterial): array {
                $material = $materials->get($materialId);

                if (! $material) {
                    throw new RuntimeException("Material {$materialId} referenced by a production plan no longer exists.");
                }

                $availableUnits = $material->stock
                    ? $this->toUnits($material->stock->on_hand, 2)
                    : 0;
                $shortageUnits = max(0, $requiredUnits - $availableUnits);
                $plans = $plansByMaterial?->get($materialId, collect()) ?? collect();

                return [
                    'material' => $material,
                    'required_quantity' => $this->fromUnits($requiredUnits),
                    'available_quantity' => $this->fromUnits($availableUnits),
                    'shortage_quantity' => $this->fromUnits($shortageUnits),
                    'status' => $shortageUnits > 0 ? 'kurang' : 'cukup',
                    'plan_count' => $plans->count(),
                    'plans' => $plans,
                ];
            })
            ->sortBy(fn (array $row): string => $row['material']->name)
            ->values();
    }

    private function toUnits(string $quantity, int $decimalPlaces): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '');
        $scale = 10 ** $decimalPlaces;

        return ((int) $whole * $scale)
            + (int) str_pad(substr($fraction, 0, $decimalPlaces), $decimalPlaces, '0');
    }

    private function fromUnits(int $units): string
    {
        return sprintf('%d.%02d', intdiv($units, self::SCALE), $units % self::SCALE);
    }
}
