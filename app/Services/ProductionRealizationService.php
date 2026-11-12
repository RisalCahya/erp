<?php

namespace App\Services;

use App\Models\FinishedGoodStock;
use App\Models\FinishedGoodStockMovement;
use App\Models\MaterialStock;
use App\Models\MaterialStockMovement;
use App\Models\Product;
use App\Models\ProductionRealization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProductionRealizationService
{
    private const UNITS_PER_QUANTITY = 100;

    private const MAX_QUANTITY_UNITS = 99_999_999_999_999;

    public function createDraft(array $attributes): ProductionRealization
    {
        return DB::transaction(function () use ($attributes): ProductionRealization {
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

            if (! filled($product->output_unit)) {
                throw ValidationException::withMessages([
                    'product_id' => 'Atur satuan output produk pada Master Produk sebelum melanjutkan.',
                ]);
            }

            $bomItems = $this->bomFor($product);
            $quantityUnits = $this->toUnits((string) $attributes['finished_good_quantity']);
            $realization = ProductionRealization::create([
                'transaction_number' => $this->generateTransactionNumber(),
                'transaction_date' => $attributes['transaction_date'],
                'product_id' => $product->id,
                'finished_good_quantity' => $this->fromUnits($quantityUnits),
                'output_unit' => $product->output_unit,
                'notes' => $attributes['notes'] ?? null,
                'status' => ProductionRealization::STATUS_DRAFT,
            ]);

            foreach ($bomItems as $bomItem) {
                $requiredUnits = $this->calculateRequiredUnits(
                    $quantityUnits,
                    $this->toUnits((string) $bomItem->quantity_per_product),
                );

                $realization->materials()->create([
                    'material_id' => $bomItem->material_id,
                    'quantity_per_output' => $bomItem->quantity_per_product,
                    'required_quantity' => $this->fromUnits($requiredUnits),
                ]);
            }

            return $realization;
        }, attempts: 3);
    }

    /**
     * @return array{product: Product, bom_items: Collection<int, array<string, mixed>>, message: ?string}
     */
    public function preview(Product $product, ?string $finishedGoodQuantity = null): array
    {
        $bomItems = $product->bomItems()
            ->with(['material.stock'])
            ->orderBy('material_id')
            ->get();

        $quantityUnits = $finishedGoodQuantity === null || $finishedGoodQuantity === ''
            ? null
            : $this->toUnits($finishedGoodQuantity);

        $rows = $bomItems->map(function ($bomItem) use ($quantityUnits): array {
            $material = $bomItem->material;

            if (! $material) {
                throw new RuntimeException("BOM item {$bomItem->id} references a missing material.");
            }

            $bomUnits = $this->toUnits((string) $bomItem->quantity_per_product);
            $requiredUnits = $quantityUnits === null
                ? null
                : $this->calculateRequiredUnits($quantityUnits, $bomUnits);
            $availableUnits = $material->stock
                ? $this->toUnits((string) $material->stock->on_hand)
                : 0;
            $shortageUnits = $requiredUnits === null ? null : max(0, $requiredUnits - $availableUnits);

            return [
                'material_id' => $material->id,
                'sku' => $material->sku,
                'name' => $material->name,
                'unit' => $material->unit,
                'quantity_per_output' => $this->fromUnits($bomUnits),
                'required_quantity' => $requiredUnits === null ? null : $this->fromUnits($requiredUnits),
                'available_quantity' => $this->fromUnits($availableUnits),
                'shortage_quantity' => $shortageUnits === null ? null : $this->fromUnits($shortageUnits),
                'status' => $shortageUnits === null ? null : ($shortageUnits > 0 ? 'kurang' : 'cukup'),
            ];
        });

        return [
            'product' => $product,
            'bom_items' => $rows,
            'message' => $rows->isEmpty() ? 'Produk ini belum memiliki BOM material.' : null,
        ];
    }

    public function post(ProductionRealization $productionRealization): ProductionRealization
    {
        return DB::transaction(function () use ($productionRealization): ProductionRealization {
            $realization = ProductionRealization::query()
                ->whereKey($productionRealization->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($realization->status !== ProductionRealization::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'production_realization' => 'Transaksi ini sudah direalisasikan dan tidak dapat diposting ulang.',
                ]);
            }

            $product = Product::query()
                ->whereKey($realization->product_id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'production_realization' => 'Produk sudah tidak aktif; realisasi tidak dapat diposting.',
                ]);
            }

            if (! filled($product->output_unit) || $product->output_unit !== $realization->output_unit) {
                throw ValidationException::withMessages([
                    'production_realization' => 'Satuan output produk berubah atau belum tersedia. Periksa Master Produk sebelum merealisasikan.',
                ]);
            }

            $bomItems = $this->bomFor($product);
            $quantityUnits = $this->toUnits((string) $realization->finished_good_quantity);
            $requirements = $bomItems->map(function ($bomItem) use ($quantityUnits): array {
                $material = $bomItem->material;
                if (! $material) {
                    throw new RuntimeException("BOM item {$bomItem->id} references a missing material.");
                }

                $requiredUnits = $this->calculateRequiredUnits(
                    $quantityUnits,
                    $this->toUnits((string) $bomItem->quantity_per_product),
                );

                return [
                    'material' => $material,
                    'quantity_per_output' => $this->fromUnits($this->toUnits((string) $bomItem->quantity_per_product)),
                    'required_units' => $requiredUnits,
                ];
            });

            $materialIds = $requirements->pluck('material.id')->sort()->values();

            foreach ($materialIds as $materialId) {
                MaterialStock::query()->firstOrCreate(
                    ['material_id' => $materialId],
                    ['on_hand' => '0.00'],
                );
            }

            $stocks = MaterialStock::query()
                ->whereIn('material_id', $materialIds)
                ->orderBy('material_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('material_id');
            $shortages = [];

            foreach ($requirements as $requirement) {
                /** @var MaterialStock|null $stock */
                $stock = $stocks->get($requirement['material']->id);
                if (! $stock) {
                    throw new RuntimeException("Stock row for material {$requirement['material']->id} could not be locked.");
                }

                $availableUnits = $this->toUnits((string) $stock->on_hand);
                if ($availableUnits < $requirement['required_units']) {
                    $shortages[] = sprintf(
                        '%s — perlu %s %s, tersedia %s %s, kurang %s %s',
                        $requirement['material']->sku,
                        $this->fromUnits($requirement['required_units']),
                        $requirement['material']->unit,
                        $this->fromUnits($availableUnits),
                        $requirement['material']->unit,
                        $this->fromUnits($requirement['required_units'] - $availableUnits),
                        $requirement['material']->unit,
                    );
                }
            }

            if ($shortages !== []) {
                throw ValidationException::withMessages([
                    'stock' => 'Stok material belum mencukupi: '.implode('; ', $shortages).'.',
                ]);
            }

            $finishedGoodStock = FinishedGoodStock::query()->firstOrCreate(
                ['product_id' => $product->id],
                ['unit' => $realization->output_unit, 'on_hand' => '0.00'],
            );
            $finishedGoodStock = FinishedGoodStock::query()
                ->whereKey($finishedGoodStock->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($finishedGoodStock->unit !== $realization->output_unit) {
                throw ValidationException::withMessages([
                    'production_realization' => 'Satuan stok barang jadi tidak sama dengan satuan transaksi.',
                ]);
            }

            $outputUnits = $this->toUnits((string) $realization->finished_good_quantity);
            $currentOutputUnits = $this->toUnits((string) $finishedGoodStock->on_hand);
            if ($outputUnits > self::MAX_QUANTITY_UNITS - $currentOutputUnits) {
                throw ValidationException::withMessages([
                    'finished_good_quantity' => 'Saldo barang jadi melebihi kapasitas yang dapat disimpan.',
                ]);
            }

            $realization->materials()->delete();

            foreach ($requirements as $requirement) {
                $material = $requirement['material'];
                $stock = $stocks->get($material->id);
                $availableUnits = $this->toUnits((string) $stock->on_hand);
                $newOnHand = $availableUnits - $requirement['required_units'];
                $stock->update(['on_hand' => $this->fromUnits($newOnHand)]);

                $realization->materials()->create([
                    'material_id' => $material->id,
                    'quantity_per_output' => $requirement['quantity_per_output'],
                    'required_quantity' => $this->fromUnits($requirement['required_units']),
                ]);

                if ($requirement['required_units'] > 0) {
                    MaterialStockMovement::create([
                        'material_id' => $material->id,
                        'movement_type' => MaterialStockMovement::TYPE_PRODUCTION_CONSUMPTION,
                        'quantity' => $this->fromUnits($requirement['required_units']),
                        'reference_type' => ProductionRealization::class,
                        'reference_id' => $realization->id,
                        'notes' => 'Konsumsi realisasi produksi '.$realization->transaction_number,
                    ]);
                }
            }

            $finishedGoodStock->update([
                'on_hand' => $this->fromUnits($currentOutputUnits + $outputUnits),
            ]);

            FinishedGoodStockMovement::create([
                'product_id' => $product->id,
                'production_realization_id' => $realization->id,
                'movement_type' => FinishedGoodStockMovement::TYPE_PRODUCTION_OUTPUT,
                'quantity' => $this->fromUnits($outputUnits),
                'unit' => $realization->output_unit,
                'notes' => 'Hasil realisasi produksi '.$realization->transaction_number,
            ]);

            $realization->update([
                'status' => ProductionRealization::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return $realization->fresh(['product', 'materials.material']);
        }, attempts: 3);
    }

    private function bomFor(Product $product): Collection
    {
        $bomItems = $product->bomItems()
            ->with('material')
            ->orderBy('material_id')
            ->lockForUpdate()
            ->get();

        if ($bomItems->isEmpty()) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk ini belum memiliki BOM material; realisasi produksi tidak dapat dilakukan.',
            ]);
        }

        foreach ($bomItems as $bomItem) {
            if (! $bomItem->material) {
                throw ValidationException::withMessages([
                    'product_id' => 'BOM mengandung material yang tidak valid.',
                ]);
            }

            if ($this->toUnits((string) $bomItem->quantity_per_product) <= 0) {
                throw ValidationException::withMessages([
                    'product_id' => 'BOM mengandung kuantitas material yang tidak valid.',
                ]);
            }
        }

        return $bomItems;
    }

    private function generateTransactionNumber(): string
    {
        return 'RP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    private function calculateRequiredUnits(int $finishedGoodUnits, int $bomUnits): int
    {
        $maximumProduct = self::MAX_QUANTITY_UNITS * self::UNITS_PER_QUANTITY + 49;

        if ($bomUnits > 0 && $finishedGoodUnits > intdiv($maximumProduct, $bomUnits)) {
            throw ValidationException::withMessages([
                'finished_good_quantity' => 'Kebutuhan material melebihi kapasitas nilai yang dapat disimpan.',
            ]);
        }

        $requiredUnits = intdiv(($finishedGoodUnits * $bomUnits) + 50, self::UNITS_PER_QUANTITY);

        if ($requiredUnits > self::MAX_QUANTITY_UNITS) {
            throw ValidationException::withMessages([
                'finished_good_quantity' => 'Kebutuhan material melebihi kapasitas nilai yang dapat disimpan.',
            ]);
        }

        return $requiredUnits;
    }

    private function toUnits(string $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '0');

        return ((int) $whole * self::UNITS_PER_QUANTITY)
            + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromUnits(int $units): string
    {
        return sprintf(
            '%d.%02d',
            intdiv($units, self::UNITS_PER_QUANTITY),
            $units % self::UNITS_PER_QUANTITY,
        );
    }
}
