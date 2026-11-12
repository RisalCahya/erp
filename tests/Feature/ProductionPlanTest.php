<?php

namespace Tests\Feature;

use App\Models\BomItem;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApplicationUsers;
use Tests\TestCase;

class ProductionPlanTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApplicationUsers;

    public function test_create_form_contains_searchable_product_dropdown_with_product_ids(): void
    {
        [$product] = $this->createProductWithBom('1.00');

        $this->get(route('production-plans.create'))
            ->assertOk()
            ->assertSee('data-product-combobox', false)
            ->assertSee('data-product-search', false)
            ->assertSee('data-product-id', false)
            ->assertSee('data-product-options', false)
            ->assertSee('data-id="'.$product->id.'"', false)
            ->assertSee('Cari SKU atau nama produk...', false)
            ->assertSee($product->sku)
            ->assertSee($product->name);
    }

    public function test_plan_creation_saves_bom_snapshot_and_shows_sufficient_stock_without_consuming_it(): void
    {
        [$product, $material, $bomItem] = $this->createProductWithBom('2.50');
        MaterialStock::create([
            'material_id' => $material->id,
            'on_hand' => '20.00',
        ]);

        $this->post(route('production-plans.store'), [
            'product_id' => $product->id,
            'planned_quantity' => '4.00',
            'due_date' => now()->addDay()->toDateString(),
        ])->assertRedirect()
            ->assertSessionHas('success');

        $plan = ProductionPlan::query()->with('materials')->firstOrFail();
        $this->assertMatchesRegularExpression('/^PP-\d{8}-[A-Z0-9]{8}$/', $plan->plan_number);
        $this->assertSame(ProductionPlan::STATUS_PLANNED, $plan->status);
        $this->assertDatabaseHas('production_plan_materials', [
            'production_plan_id' => $plan->id,
            'material_id' => $material->id,
            'required_quantity' => '10.00',
        ]);

        $bomItem->update(['quantity_per_product' => '50.00']);

        $this->get(route('production-plans.show', $plan))
            ->assertOk()
            ->assertSee('10.00')
            ->assertSee('20.00')
            ->assertSee('Material cukup')
            ->assertSee('Kebutuhan Material Rencana Ini')
            ->assertDontSee('Kebutuhan Gabungan Semua Rencana Aktif');

        $this->assertDatabaseHas('material_stocks', [
            'material_id' => $material->id,
            'on_hand' => '20.00',
        ]);
        $this->assertDatabaseCount('material_stock_movements', 0);

        $this->get(route('production-plans.index'))
            ->assertOk()
            ->assertSee($plan->plan_number)
            ->assertSee($product->name);
    }

    public function test_plan_detail_reports_material_shortage(): void
    {
        [$product, $material] = $this->createProductWithBom('3.00');
        MaterialStock::create([
            'material_id' => $material->id,
            'on_hand' => '5.00',
        ]);

        $plan = $this->createPlan($product, '4.0000');

        $this->get(route('production-plans.show', $plan))
            ->assertOk()
            ->assertSee('12.00')
            ->assertSee('5.00')
            ->assertSee('7.00')
            ->assertSee('Kurang');
    }

    public function test_plan_validation_rejects_nonpositive_quantity_invalid_date_and_inactive_product(): void
    {
        [$product] = $this->createProductWithBom('1.00');

        foreach (['0', '-1', '1.001'] as $quantity) {
            $this->from(route('production-plans.create'))
                ->post(route('production-plans.store'), [
                    'product_id' => $product->id,
                    'planned_quantity' => $quantity,
                    'due_date' => now()->addDay()->toDateString(),
                ])
                ->assertRedirect(route('production-plans.create'))
                ->assertSessionHasErrors('planned_quantity');
        }

        $this->from(route('production-plans.create'))
            ->post(route('production-plans.store'), [
                'product_id' => $product->id,
                'planned_quantity' => '1.00',
                'due_date' => now()->subDay()->toDateString(),
            ])
            ->assertRedirect(route('production-plans.create'))
            ->assertSessionHasErrors('due_date');

        $product->update(['is_active' => false]);

        $this->from(route('production-plans.create'))
            ->post(route('production-plans.store'), [
                'product_id' => $product->id,
                'planned_quantity' => '1.00',
                'due_date' => now()->addDay()->toDateString(),
            ])
            ->assertRedirect(route('production-plans.create'))
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('production_plans', 0);
    }

    public function test_product_without_bom_cannot_be_planned(): void
    {
        $product = Product::create([
            'sku' => 'PRD-NO-BOM',
            'name' => 'Product without BOM',
            'is_active' => true,
        ]);

        $this->from(route('production-plans.create'))
            ->post(route('production-plans.store'), [
                'product_id' => $product->id,
                'planned_quantity' => '1.00',
                'due_date' => now()->addDay()->toDateString(),
            ])
            ->assertRedirect(route('production-plans.create'))
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('production_plans', 0);
    }

    public function test_planned_requirements_are_aggregated_before_stock_is_compared(): void
    {
        [$product, $material] = $this->createProductWithBom('4.00');
        MaterialStock::create([
            'material_id' => $material->id,
            'on_hand' => '6.00',
        ]);

        $this->createPlan($product, '1.0000');
        $secondPlan = $this->createPlan($product, '1.0000');

        $this->get(route('production-plans.show', $secondPlan))
            ->assertOk()
            ->assertSee('4.00')
            ->assertSee('6.00')
            ->assertSee('Material cukup')
            ->assertDontSee('8.00');
    }

    public function test_material_requirements_aggregate_planned_snapshots_and_compare_stock_once(): void
    {
        [$firstProduct, $material, $bomItem] = $this->createProductWithBom('4.00');
        $secondProduct = Product::create([
            'sku' => 'PRD-PLAN-SECOND',
            'name' => 'Second Plan Product',
            'is_active' => true,
        ]);
        BomItem::create([
            'product_id' => $secondProduct->id,
            'material_id' => $material->id,
            'quantity_per_product' => '4.00',
        ]);
        MaterialStock::create(['material_id' => $material->id, 'on_hand' => '6.00']);

        $firstPlan = $this->createPlan($firstProduct, '1.00');
        $secondPlan = $this->createPlan($secondProduct, '1.00');
        $bomItem->update(['quantity_per_product' => '100.00']);

        $this->get(route('material-requirements.index'))
            ->assertOk()
            ->assertSee($material->sku)
            ->assertSee('8.00')
            ->assertSee('6.00')
            ->assertSee('2.00')
            ->assertSee('Kurang')
            ->assertSee('Rencana diperhitungkan')
            ->assertSee('>'.$firstPlan->plan_number.'</a>', false)
            ->assertSee('>'.$secondPlan->plan_number.'</a>', false)
            ->assertSee('name="due_date_from"', false)
            ->assertSee('name="due_date_to"', false);

        $this->assertDatabaseHas('material_stocks', [
            'material_id' => $material->id,
            'on_hand' => '6.00',
        ]);
    }

    public function test_material_requirement_filters_by_due_date_and_product(): void
    {
        [$firstProduct, $firstMaterial] = $this->createProductWithBom('2.00');
        [$secondProduct, $secondMaterial] = $this->createProductWithBom('7.00');
        $firstPlan = $this->createPlan($firstProduct, '3.00');
        $firstPlan->update(['due_date' => '2026-10-30']);
        $secondPlan = $this->createPlan($secondProduct, '5.00');
        $secondPlan->update(['due_date' => '2026-11-05']);

        $this->get(route('material-requirements.index', [
            'product_id' => $firstProduct->id,
            'due_date_from' => '2026-10-30',
            'due_date_to' => '2026-10-30',
        ]))
            ->assertOk()
            ->assertSee($firstMaterial->sku)
            ->assertDontSee($secondMaterial->sku)
            ->assertSee('6.00')
            ->assertSee('>1</p>', false)
            ->assertSee($firstPlan->plan_number)
            ->assertDontSee($secondPlan->plan_number);
    }

    public function test_material_requirement_summary_excludes_nonplanned_statuses(): void
    {
        [$product, $material] = $this->createProductWithBom('2.00');
        MaterialStock::create(['material_id' => $material->id, 'on_hand' => '3.00']);
        $planned = $this->createPlan($product, '2.00');
        $draft = $this->createPlan($product, '50.00', ProductionPlan::STATUS_DRAFT);
        $cancelled = $this->createPlan($product, '60.00', ProductionPlan::STATUS_CANCELLED);
        $completed = $this->createPlan($product, '70.00', ProductionPlan::STATUS_COMPLETED);

        $this->get(route('material-requirements.index'))
            ->assertOk()
            ->assertSee('4.00')
            ->assertSee('3.00')
            ->assertSee('1.00')
            ->assertSee('>'.$planned->plan_number.'</a>', false)
            ->assertDontSee($draft->plan_number)
            ->assertDontSee($cancelled->plan_number)
            ->assertDontSee($completed->plan_number)
            ->assertSee('>1</p>', false);
    }

    public function test_required_quantity_is_rounded_to_two_decimal_places(): void
    {
        [$product] = $this->createProductWithBom('2.50');

        $this->post(route('production-plans.store'), [
            'product_id' => $product->id,
            'planned_quantity' => '1.25',
            'due_date' => now()->addDay()->toDateString(),
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('production_plan_materials', [
            'required_quantity' => '3.13',
        ]);
    }

    public function test_deleting_plan_removes_its_snapshot_without_changing_stock(): void
    {
        [$product, $material] = $this->createProductWithBom('2.50');
        MaterialStock::create([
            'material_id' => $material->id,
            'on_hand' => '12.00',
        ]);
        $plan = $this->createPlan($product, '4.00');
        $snapshotId = $plan->materials()->value('id');

        $this->delete(route('production-plans.destroy', $plan))
            ->assertRedirect(route('production-plans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('production_plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('production_plan_materials', ['id' => $snapshotId]);
        $this->assertDatabaseHas('material_stocks', [
            'material_id' => $material->id,
            'on_hand' => '12.00',
        ]);
        $this->assertDatabaseCount('material_stock_movements', 0);
    }

    /**
     * @return array{Product, Material, BomItem}
     */
    private function createProductWithBom(string $quantityPerProduct): array
    {
        $product = Product::create([
            'sku' => 'PRD-PLAN-'.fake()->unique()->numerify('####'),
            'name' => 'Plan Product',
            'is_active' => true,
        ]);
        $material = Material::create([
            'sku' => 'MAT-PLAN-'.fake()->unique()->numerify('####'),
            'name' => 'Plan Material',
            'unit' => 'kg',
            'is_active' => true,
        ]);
        $bomItem = BomItem::create([
            'product_id' => $product->id,
            'material_id' => $material->id,
            'quantity_per_product' => $quantityPerProduct,
        ]);

        return [$product, $material, $bomItem];
    }

    private function createPlan(
        Product $product,
        string $quantity,
        string $status = ProductionPlan::STATUS_PLANNED,
    ): ProductionPlan
    {
        $plan = ProductionPlan::create([
            'plan_number' => 'PP-TEST-'.fake()->unique()->numerify('#####'),
            'product_id' => $product->id,
            'planned_quantity' => $quantity,
            'due_date' => now()->addDay()->toDateString(),
            'status' => $status,
        ]);

        $bomItems = $product->bomItems()->get();

        foreach ($bomItems as $bomItem) {
            ProductionPlanMaterial::create([
                'production_plan_id' => $plan->id,
                'material_id' => $bomItem->material_id,
                'required_quantity' => number_format(
                    (float) $quantity * (float) $bomItem->quantity_per_product,
                    2,
                    '.',
                    '',
                ),
            ]);
        }

        return $plan;
    }
}
