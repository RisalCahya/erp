<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanMaterial;
use App\Models\ProductionRealization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApplicationUsers;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApplicationUsers;

    public function test_dashboard_displays_database_summary_counts(): void
    {
        Product::create(['sku' => 'PRD-DASH-01', 'name' => 'Active Product', 'is_active' => true]);
        Product::create(['sku' => 'PRD-DASH-02', 'name' => 'Inactive Product', 'is_active' => false]);
        Material::create(['sku' => 'MAT-DASH-01', 'name' => 'Active Material', 'unit' => 'pcs', 'is_active' => true]);
        Material::create(['sku' => 'MAT-DASH-02', 'name' => 'Inactive Material', 'unit' => 'kg', 'is_active' => false]);
        $product = Product::query()->where('sku', 'PRD-DASH-01')->firstOrFail();
        $planned = $this->createPlan($product, 'PP-DASH-PLANNED', ProductionPlan::STATUS_PLANNED);
        $this->createPlan($product, 'PP-DASH-DRAFT', ProductionPlan::STATUS_DRAFT);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Produk aktif')
            ->assertSee('Material aktif')
            ->assertSee('Rencana aktif')
            ->assertSee('>1</p>', false)
            ->assertSee($planned->plan_number);
    }

    public function test_dashboard_aggregates_planned_snapshots_and_excludes_other_plan_statuses(): void
    {
        $product = Product::create([
            'sku' => 'PRD-DASH-MRP',
            'name' => 'Dashboard MRP Product',
            'is_active' => true,
        ]);
        $material = Material::create([
            'sku' => 'MAT-DASH-MRP',
            'name' => 'Dashboard MRP Material',
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        MaterialStock::create(['material_id' => $material->id, 'on_hand' => '5.00']);

        $firstPlan = $this->createPlan($product, 'PP-DASH-FIRST', ProductionPlan::STATUS_PLANNED);
        $secondPlan = $this->createPlan($product, 'PP-DASH-SECOND', ProductionPlan::STATUS_PLANNED);
        $draftPlan = $this->createPlan($product, 'PP-DASH-DRAFT-EXCLUDED', ProductionPlan::STATUS_DRAFT);
        $cancelledPlan = $this->createPlan($product, 'PP-DASH-CANCELLED-EXCLUDED', ProductionPlan::STATUS_CANCELLED);
        $completedPlan = $this->createPlan($product, 'PP-DASH-COMPLETED-EXCLUDED', ProductionPlan::STATUS_COMPLETED);

        foreach ([$firstPlan, $secondPlan] as $plan) {
            $this->createPlanMaterial($plan, $material, '6.00');
        }
        foreach ([$draftPlan, $cancelledPlan, $completedPlan] as $plan) {
            $this->createPlanMaterial($plan, $material, '100.00');
        }

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($material->sku)
            ->assertSee('12.00')
            ->assertSee('5.00')
            ->assertSee('7.00')
            ->assertSee($firstPlan->plan_number)
            ->assertSee($secondPlan->plan_number)
            ->assertDontSee($draftPlan->plan_number)
            ->assertDontSee($cancelledPlan->plan_number)
            ->assertDontSee($completedPlan->plan_number);
    }

    public function test_dashboard_displays_recent_realizations_with_detail_links(): void
    {
        $product = Product::create([
            'sku' => 'PRD-DASH-REAL',
            'name' => 'Realized Product',
            'output_unit' => 'karton',
            'is_active' => true,
        ]);
        $older = $this->createRealization($product, 'RP-DASH-OLDER', '2026-10-20', 'draft', '25.00');
        $newest = $this->createRealization($product, 'RP-DASH-NEWEST', '2026-10-29', 'completed', '50.00');

        $response = $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aktivitas Realisasi Produksi')
            ->assertSee($newest->transaction_number)
            ->assertSee('50.00 karton')
            ->assertSee('Selesai')
            ->assertSee(route('production-realizations.show', $newest), false)
            ->assertSee($older->transaction_number)
            ->assertSee(route('production-realizations.show', $older), false);

        $this->assertLessThan(
            strpos($response->getContent(), $older->transaction_number),
            strpos($response->getContent(), $newest->transaction_number),
        );
    }

    public function test_opening_dashboard_does_not_change_material_stock(): void
    {
        $product = Product::create([
            'sku' => 'PRD-DASH-STOCK',
            'name' => 'Dashboard Stock Product',
            'is_active' => true,
        ]);
        $material = Material::create([
            'sku' => 'MAT-DASH-STOCK',
            'name' => 'Dashboard Stock Material',
            'unit' => 'kg',
            'is_active' => true,
        ]);
        $stock = MaterialStock::create(['material_id' => $material->id, 'on_hand' => '17.50']);
        $plan = $this->createPlan($product, 'PP-DASH-STOCK', ProductionPlan::STATUS_PLANNED);
        $this->createPlanMaterial($plan, $material, '20.00');

        $this->get(route('dashboard'))->assertOk();

        $this->assertDatabaseHas('material_stocks', [
            'id' => $stock->id,
            'material_id' => $material->id,
            'on_hand' => '17.50',
        ]);
        $this->assertDatabaseCount('material_stock_movements', 0);
    }

    private function createPlan(Product $product, string $number, string $status): ProductionPlan
    {
        return ProductionPlan::create([
            'plan_number' => $number,
            'product_id' => $product->id,
            'planned_quantity' => '1.00',
            'due_date' => now()->addDays(2)->toDateString(),
            'status' => $status,
        ]);
    }

    private function createPlanMaterial(
        ProductionPlan $plan,
        Material $material,
        string $requiredQuantity,
    ): ProductionPlanMaterial {
        return ProductionPlanMaterial::create([
            'production_plan_id' => $plan->id,
            'material_id' => $material->id,
            'required_quantity' => $requiredQuantity,
        ]);
    }

    private function createRealization(
        Product $product,
        string $number,
        string $date,
        string $status,
        string $quantity,
    ): ProductionRealization {
        return ProductionRealization::create([
            'transaction_number' => $number,
            'transaction_date' => $date,
            'product_id' => $product->id,
            'finished_good_quantity' => $quantity,
            'output_unit' => 'karton',
            'status' => $status,
        ]);
    }
}
