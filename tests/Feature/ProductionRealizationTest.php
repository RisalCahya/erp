<?php

namespace Tests\Feature;

use App\Models\BomItem;
use App\Models\FinishedGoodStock;
use App\Models\FinishedGoodStockMovement;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialStockMovement;
use App\Models\Product;
use App\Models\ProductionRealization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApplicationUsers;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionRealizationTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApplicationUsers;

    public function test_product_preview_displays_its_bom_and_calculates_500_output_units(): void
    {
        [$product, $cup, $lid, $carton] = $this->createPackagingProduct();
        MaterialStock::create(['material_id' => $cup->id, 'on_hand' => '30000.00']);
        MaterialStock::create(['material_id' => $lid->id, 'on_hand' => '24000.00']);
        MaterialStock::create(['material_id' => $carton->id, 'on_hand' => '800.00']);

        $this->get(route('production-realizations.products.preview', [
            'product' => $product,
            'finished_good_quantity' => '500.00',
        ]))
            ->assertOk()
            ->assertJsonPath('product.sku', $product->sku)
            ->assertJsonPath('product.output_unit', 'karton')
            ->assertJsonFragment([
                'sku' => $cup->sku,
                'unit' => 'pcs',
                'quantity_per_output' => '48.00',
                'required_quantity' => '24000.00',
                'available_quantity' => '30000.00',
                'shortage_quantity' => '0.00',
                'status' => 'cukup',
            ])
            ->assertJsonFragment([
                'sku' => $carton->sku,
                'quantity_per_output' => '1.00',
                'required_quantity' => '500.00',
                'available_quantity' => '800.00',
            ]);
    }

    public function test_form_displays_searchable_product_and_formula_columns(): void
    {
        [$product] = $this->createPackagingProduct();

        $this->get(route('production-realizations.create'))
            ->assertOk()
            ->assertSee($product->sku)
            ->assertSee('data-product-combobox', false)
            ->assertSee('data-product-search', false)
            ->assertSee('data-product-options', false)
            ->assertSee('Qty Finished Good')
            ->assertSee('Qty BOM / Output')
            ->assertSee('Satuan Material')
            ->assertSee('Qty. Pakai')
            ->assertSee('Simpan Draft');
    }

    public function test_changing_finished_good_quantity_recalculates_preview_without_changing_bom_or_stock(): void
    {
        [$product, $cup] = $this->createPackagingProduct();
        MaterialStock::create(['material_id' => $cup->id, 'on_hand' => '40000.00']);
        $bomQuantity = $product->bomItems()->where('material_id', $cup->id)->value('quantity_per_product');

        $this->get(route('production-realizations.products.preview', [
            'product' => $product,
            'finished_good_quantity' => '500.00',
        ]))->assertJsonFragment([
            'sku' => $cup->sku,
            'quantity_per_output' => '48.00',
            'required_quantity' => '24000.00',
        ]);

        $this->get(route('production-realizations.products.preview', [
            'product' => $product,
            'finished_good_quantity' => '250.00',
        ]))->assertJsonFragment([
            'sku' => $cup->sku,
            'quantity_per_output' => '48.00',
            'required_quantity' => '12000.00',
        ]);

        $this->assertSame($bomQuantity, $product->bomItems()->where('material_id', $cup->id)->value('quantity_per_product'));
        $this->assertDatabaseHas('material_stocks', ['material_id' => $cup->id, 'on_hand' => '40000.00']);
        $this->assertDatabaseCount('material_stock_movements', 0);
    }

    public function test_saving_draft_does_not_change_material_stock(): void
    {
        [$product, $cup] = $this->createPackagingProduct();
        MaterialStock::create(['material_id' => $cup->id, 'on_hand' => '30000.00']);

        $realization = $this->saveDraft($product, '500.00');

        $this->assertDatabaseHas('production_realizations', [
            'id' => $realization->id,
            'status' => ProductionRealization::STATUS_DRAFT,
            'finished_good_quantity' => '500.00',
            'output_unit' => 'karton',
        ]);
        $this->assertMatchesRegularExpression('/^RP-\d{8}-[A-Z0-9]{8}$/', $realization->transaction_number);
        $this->assertDatabaseHas('production_realization_materials', [
            'production_realization_id' => $realization->id,
            'material_id' => $cup->id,
            'quantity_per_output' => '48.00',
            'required_quantity' => '24000.00',
        ]);
        $this->assertDatabaseHas('material_stocks', ['material_id' => $cup->id, 'on_hand' => '30000.00']);
        $this->assertDatabaseCount('material_stock_movements', 0);
    }

    public function test_empty_bom_rejects_production_realization(): void
    {
        [$product, $cup] = $this->createPackagingProduct();
        $realization = $this->saveDraft($product, '10.00');
        $product->bomItems()->delete();

        $this->from(route('production-realizations.show', $realization))
            ->post(route('production-realizations.post', $realization))
            ->assertRedirect(route('production-realizations.show', $realization))
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseHas('production_realizations', [
            'id' => $realization->id,
            'status' => ProductionRealization::STATUS_DRAFT,
        ]);
        $this->assertDatabaseMissing('material_stocks', ['material_id' => $cup->id]);
    }

    public function test_sufficient_stock_posts_consumption_movements_and_finished_goods_output(): void
    {
        [$product, $cup, $lid, $carton] = $this->createPackagingProduct();
        foreach ([$cup, $lid] as $material) {
            MaterialStock::create(['material_id' => $material->id, 'on_hand' => '25000.00']);
        }
        MaterialStock::create(['material_id' => $carton->id, 'on_hand' => '500.00']);
        $realization = $this->saveDraft($product, '500.00');

        $this->from(route('production-realizations.show', $realization))
            ->post(route('production-realizations.post', $realization))
            ->assertRedirect(route('production-realizations.show', $realization))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('production_realizations', [
            'id' => $realization->id,
            'status' => ProductionRealization::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('material_stocks', ['material_id' => $cup->id, 'on_hand' => '1000.00']);
        $this->assertDatabaseHas('material_stocks', ['material_id' => $lid->id, 'on_hand' => '1000.00']);
        $this->assertDatabaseHas('material_stocks', ['material_id' => $carton->id, 'on_hand' => '0.00']);
        $this->assertDatabaseHas('material_stock_movements', [
            'material_id' => $cup->id,
            'movement_type' => MaterialStockMovement::TYPE_PRODUCTION_CONSUMPTION,
            'quantity' => '24000.00',
            'reference_type' => ProductionRealization::class,
            'reference_id' => $realization->id,
        ]);
        $this->assertDatabaseHas('finished_good_stocks', [
            'product_id' => $product->id,
            'unit' => 'karton',
            'on_hand' => '500.00',
        ]);
        $this->assertDatabaseHas('finished_good_stock_movements', [
            'product_id' => $product->id,
            'production_realization_id' => $realization->id,
            'movement_type' => FinishedGoodStockMovement::TYPE_PRODUCTION_OUTPUT,
            'quantity' => '500.00',
            'unit' => 'karton',
        ]);
    }

    public function test_inventory_consumption_reference_links_to_production_realization(): void
    {
        [$product, $cup, $lid, $carton] = $this->createPackagingProduct();
        MaterialStock::create(['material_id' => $cup->id, 'on_hand' => '25000.00']);
        MaterialStock::create(['material_id' => $lid->id, 'on_hand' => '25000.00']);
        MaterialStock::create(['material_id' => $carton->id, 'on_hand' => '500.00']);
        $realization = $this->saveDraft($product, '500.00');
        $this->post(route('production-realizations.post', $realization))->assertRedirect();

        $movement = MaterialStockMovement::query()->firstOrFail();
        $this->assertSame(ProductionRealization::class, $movement->reference_type);
        $this->assertSame($realization->id, $movement->reference_id);
        $this->assertInstanceOf(ProductionRealization::class, $movement->reference);

        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSee(route('production-realizations.show', $realization), false)
            ->assertSee($realization->transaction_number)
            ->assertSee('Konsumsi realisasi produksi');
    }

    public function test_insufficient_stock_rejects_post_without_changing_any_stock(): void
    {
        [$product, $cup, $lid] = $this->createPackagingProduct();
        MaterialStock::create(['material_id' => $cup->id, 'on_hand' => '30000.00']);
        MaterialStock::create(['material_id' => $lid->id, 'on_hand' => '100.00']);
        $realization = $this->saveDraft($product, '500.00');

        $this->from(route('production-realizations.show', $realization))
            ->post(route('production-realizations.post', $realization))
            ->assertRedirect(route('production-realizations.show', $realization))
            ->assertSessionHasErrors('stock')
            ->assertSessionHas('errors', function ($errors) use ($lid): bool {
                return str_contains($errors->first('stock'), $lid->sku)
                    && str_contains($errors->first('stock'), '23900.00 pcs');
            });

        $this->assertDatabaseHas('material_stocks', ['material_id' => $cup->id, 'on_hand' => '30000.00']);
        $this->assertDatabaseHas('material_stocks', ['material_id' => $lid->id, 'on_hand' => '100.00']);
        $this->assertDatabaseCount('material_stock_movements', 0);
        $this->assertDatabaseMissing('finished_good_stocks', ['product_id' => $product->id]);
        $this->assertDatabaseHas('production_realizations', [
            'id' => $realization->id,
            'status' => ProductionRealization::STATUS_DRAFT,
        ]);
    }

    public function test_completed_realization_cannot_be_posted_twice(): void
    {
        [$product, $cup] = $this->createPackagingProduct();
        MaterialStock::create(['material_id' => $cup->id, 'on_hand' => '100.00']);
        foreach ($product->bomItems()->where('material_id', '!=', $cup->id)->get() as $bomItem) {
            MaterialStock::create(['material_id' => $bomItem->material_id, 'on_hand' => '100.00']);
        }
        $realization = $this->saveDraft($product, '2.00');

        $this->post(route('production-realizations.post', $realization))->assertRedirect();
        $this->from(route('production-realizations.show', $realization))
            ->post(route('production-realizations.post', $realization))
            ->assertRedirect(route('production-realizations.show', $realization))
            ->assertSessionHasErrors('production_realization');

        $this->assertDatabaseHas('material_stocks', ['material_id' => $cup->id, 'on_hand' => '4.00']);
        $this->assertDatabaseCount('material_stock_movements', 3);
        $this->assertDatabaseCount('finished_good_stock_movements', 1);
    }

    public function test_failure_during_movement_creation_rolls_back_all_stock_and_history_changes(): void
    {
        [$product, $cup, $lid] = $this->createPackagingProduct();
        MaterialStock::create(['material_id' => $cup->id, 'on_hand' => '1000.00']);
        MaterialStock::create(['material_id' => $lid->id, 'on_hand' => '1000.00']);
        $carton = $product->bomItems()->where('material_id', '!=', $cup->id)->where('material_id', '!=', $lid->id)->firstOrFail();
        MaterialStock::create(['material_id' => $carton->material_id, 'on_hand' => '1000.00']);
        $realization = $this->saveDraft($product, '10.00');

        DB::statement(
            "CREATE TRIGGER fail_second_realization_movement BEFORE INSERT ON material_stock_movements ".
            "WHEN NEW.material_id = {$lid->id} BEGIN SELECT RAISE(ABORT, 'simulated movement failure'); END",
        );

        $this->post(route('production-realizations.post', $realization))->assertServerError();

        $this->assertDatabaseHas('material_stocks', ['material_id' => $cup->id, 'on_hand' => '1000.00']);
        $this->assertDatabaseHas('material_stocks', ['material_id' => $lid->id, 'on_hand' => '1000.00']);
        $this->assertDatabaseCount('material_stock_movements', 0);
        $this->assertDatabaseCount('finished_good_stocks', 0);
        $this->assertDatabaseCount('finished_good_stock_movements', 0);
        $this->assertDatabaseHas('production_realizations', [
            'id' => $realization->id,
            'status' => ProductionRealization::STATUS_DRAFT,
        ]);
    }

    public function test_invalid_quantity_and_missing_output_unit_are_rejected(): void
    {
        [$product] = $this->createPackagingProduct();

        foreach (['0', '-1', '1.001'] as $quantity) {
            $this->from(route('production-realizations.create'))
                ->post(route('production-realizations.store'), [
                    'transaction_date' => now()->toDateString(),
                    'product_id' => $product->id,
                    'finished_good_quantity' => $quantity,
                ])
                ->assertRedirect(route('production-realizations.create'))
                ->assertSessionHasErrors('finished_good_quantity');
        }

        $product->update(['output_unit' => null]);
        $this->from(route('production-realizations.create'))
            ->post(route('production-realizations.store'), [
                'transaction_date' => now()->toDateString(),
                'product_id' => $product->id,
                'finished_good_quantity' => '1.00',
            ])
            ->assertRedirect(route('production-realizations.create'))
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('production_realizations', 0);
    }

    public function test_product_output_unit_cannot_be_changed_after_a_realization_exists(): void
    {
        [$product] = $this->createPackagingProduct();
        $this->saveDraft($product, '10.00');

        $this->from(route('products.edit', $product))
            ->put(route('products.update', $product), [
                'sku' => $product->sku,
                'name' => $product->name,
                'output_unit' => 'dus',
                'description' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('products.edit', $product))
            ->assertSessionHasErrors('output_unit');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'output_unit' => 'karton',
        ]);
    }

    public function test_product_and_material_with_realization_history_cannot_be_deleted(): void
    {
        [$product, $cup] = $this->createPackagingProduct();
        $realization = $this->saveDraft($product, '10.00');

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error');
        $this->delete(route('materials.destroy', $cup))
            ->assertRedirect(route('materials.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('materials', ['id' => $cup->id]);
        $this->assertDatabaseHas('production_realizations', ['id' => $realization->id]);
    }

    /**
     * @return array{Product, Material, Material, Material}
     */
    private function createPackagingProduct(): array
    {
        $product = Product::create([
            'sku' => 'PRD-PACK-'.fake()->unique()->numerify('####'),
            'name' => 'Product Carton Pack',
            'output_unit' => 'karton',
            'is_active' => true,
        ]);
        $cup = $this->createMaterial('MAT-CUP', 'Cup', 'pcs');
        $lid = $this->createMaterial('MAT-LID', 'Lid', 'pcs');
        $carton = $this->createMaterial('MAT-CARTON', 'Carton', 'pcs');

        foreach ([[$cup, '48.00'], [$lid, '48.00'], [$carton, '1.00']] as [$material, $quantity]) {
            BomItem::create([
                'product_id' => $product->id,
                'material_id' => $material->id,
                'quantity_per_product' => $quantity,
            ]);
        }

        return [$product, $cup, $lid, $carton];
    }

    private function createMaterial(string $prefix, string $name, string $unit): Material
    {
        return Material::create([
            'sku' => $prefix.'-'.fake()->unique()->numerify('####'),
            'name' => $name,
            'unit' => $unit,
            'is_active' => true,
        ]);
    }

    private function saveDraft(Product $product, string $quantity): ProductionRealization
    {
        $this->post(route('production-realizations.store'), [
            'transaction_date' => now()->toDateString(),
            'product_id' => $product->id,
            'finished_good_quantity' => $quantity,
            'notes' => 'Test portfolio',
        ])->assertRedirect()
            ->assertSessionHas('success');

        return ProductionRealization::query()->latest('id')->firstOrFail();
    }
}
