<?php

namespace Tests\Feature;

use App\Models\BomItem;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApplicationUsers;
use Tests\TestCase;

class BomItemTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApplicationUsers;

    public function test_bom_items_can_be_created_listed_updated_and_deleted(): void
    {
        $product = Product::create([
            'sku' => 'PRD-BOM-01',
            'name' => 'Assembly A',
            'is_active' => true,
        ]);
        $material = Material::create([
            'sku' => 'MAT-BOM-01',
            'name' => 'Steel',
            'unit' => 'kg',
            'is_active' => true,
        ]);
        $inactiveMaterial = Material::create([
            'sku' => 'MAT-BOM-02',
            'name' => 'Inactive component',
            'unit' => 'pcs',
            'is_active' => false,
        ]);

        $this->get(route('bom.index'))
            ->assertOk()
            ->assertSee('data-product-combobox', false)
            ->assertSee('data-product-search', false)
            ->assertSee('data-product-options', false)
            ->assertSee('data-id="'.$product->id.'"', false)
            ->assertSee('Pilih produk terlebih dahulu');

        $this->get(route('bom.index', ['product_id' => $product->id]))
            ->assertOk()
            ->assertSee('Assembly A')
            ->assertSee('Steel')
            ->assertDontSee('Inactive component');

        $this->post(route('bom-items.store', $product), [
            'material_id' => $material->id,
            'quantity_per_product' => '2.50',
        ])->assertRedirect(route('bom.index', ['product_id' => $product->id]))
            ->assertSessionHas('success');

        $bomItem = BomItem::query()->where('product_id', $product->id)->firstOrFail();

        $this->get(route('bom-items.edit', $bomItem))
            ->assertOk()
            ->assertSee('Edit Kuantitas Material')
            ->assertSee($material->unit);

        $this->get(route('bom.index', ['product_id' => $product->id]))
            ->assertOk()
            ->assertSee('2.50')
            ->assertSee('kg');

        $this->put(route('bom-items.update', $bomItem), [
            'quantity_per_product' => '1.25',
        ])->assertRedirect(route('bom.index', ['product_id' => $product->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bom_items', [
            'id' => $bomItem->id,
            'quantity_per_product' => '1.25',
        ]);

        $this->delete(route('bom-items.destroy', $bomItem))
            ->assertRedirect(route('bom.index', ['product_id' => $product->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bom_items', ['id' => $bomItem->id]);
    }

    public function test_bom_rejects_duplicate_material_pairs_and_invalid_quantities(): void
    {
        $product = Product::create([
            'sku' => 'PRD-BOM-02',
            'name' => 'Assembly B',
            'is_active' => true,
        ]);
        $material = Material::create([
            'sku' => 'MAT-BOM-03',
            'name' => 'Fastener',
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        BomItem::create([
            'product_id' => $product->id,
            'material_id' => $material->id,
            'quantity_per_product' => '4.00',
        ]);

        $this->from(route('bom.index', ['product_id' => $product->id]))
            ->post(route('bom-items.store', $product), [
                'material_id' => $material->id,
                'quantity_per_product' => '2.00',
            ])
            ->assertRedirect(route('bom.index', ['product_id' => $product->id]))
            ->assertSessionHasErrors('material_id');

        $bomItem = BomItem::query()->firstOrFail();

        foreach (['0', '-1', '1.234'] as $quantity) {
            $this->from(route('bom.index', ['product_id' => $product->id]))
                ->put(route('bom-items.update', $bomItem), [
                    'quantity_per_product' => $quantity,
                ])
                ->assertRedirect(route('bom.index', ['product_id' => $product->id]))
                ->assertSessionHasErrors('quantity_per_product');
        }

        $this->from(route('bom.index', ['product_id' => $product->id]))
            ->post(route('bom-items.store', $product), [
                'material_id' => Material::create([
                    'sku' => 'MAT-BOM-06',
                    'name' => 'Third decimal test',
                    'unit' => 'kg',
                    'is_active' => true,
                ])->id,
                'quantity_per_product' => '1.234',
            ])
            ->assertRedirect(route('bom.index', ['product_id' => $product->id]))
            ->assertSessionHasErrors('quantity_per_product');
    }

    public function test_inactive_products_and_materials_cannot_be_added_to_a_bom(): void
    {
        $activeProduct = Product::create([
            'sku' => 'PRD-BOM-03',
            'name' => 'Assembly C',
            'is_active' => true,
        ]);
        $inactiveProduct = Product::create([
            'sku' => 'PRD-BOM-04',
            'name' => 'Inactive assembly',
            'is_active' => false,
        ]);
        $activeMaterial = Material::create([
            'sku' => 'MAT-BOM-04',
            'name' => 'Active part',
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $inactiveMaterial = Material::create([
            'sku' => 'MAT-BOM-05',
            'name' => 'Inactive part',
            'unit' => 'kg',
            'is_active' => false,
        ]);

        $this->from(route('bom.index', ['product_id' => $inactiveProduct->id]))
            ->post(route('bom-items.store', $inactiveProduct), [
                'material_id' => $activeMaterial->id,
                'quantity_per_product' => '1.00',
            ])
            ->assertRedirect(route('bom.index', ['product_id' => $inactiveProduct->id]))
            ->assertSessionHasErrors('product');

        $this->from(route('bom.index', ['product_id' => $activeProduct->id]))
            ->post(route('bom-items.store', $activeProduct), [
                'material_id' => $inactiveMaterial->id,
                'quantity_per_product' => '1.00',
            ])
            ->assertRedirect(route('bom.index', ['product_id' => $activeProduct->id]))
            ->assertSessionHasErrors('material_id');

        $this->assertDatabaseCount('bom_items', 0);
    }
}
