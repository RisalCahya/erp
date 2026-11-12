<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialStockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApplicationUsers;
use Tests\TestCase;

class MaterialInventoryTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApplicationUsers;

    public function test_inventory_shows_materials_without_stock_as_zero(): void
    {
        $material = Material::create([
            'sku' => 'MAT-STOCK-01',
            'name' => 'Aluminum',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSee($material->sku)
            ->assertSee('0.00')
            ->assertSee('kg');

        $this->assertDatabaseMissing('material_stocks', ['material_id' => $material->id]);
    }

    public function test_receipt_and_adjustment_forms_have_searchable_material_dropdowns(): void
    {
        $material = Material::create([
            'sku' => 'MAT-SEARCH-01',
            'name' => 'Searchable packaging',
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('data-material-combobox', false)
            ->assertSee('data-material-search', false)
            ->assertSee('data-material-id', false)
            ->assertSee('data-material-options', false)
            ->assertSee('data-id="'.$material->id.'"', false)
            ->assertSee($material->sku)
            ->assertSee($material->name)
            ->assertSee('Cari SKU atau nama material...', false);
    }

    public function test_receipt_and_physical_count_adjustment_update_stock_and_log_movements(): void
    {
        $material = Material::create([
            'sku' => 'MAT-STOCK-02',
            'name' => 'Copper wire',
            'unit' => 'm',
            'is_active' => true,
        ]);

        $this->post(route('inventory.receipts.store'), [
            'material_id' => $material->id,
            'quantity' => '10.25',
            'notes' => 'Penerimaan awal',
            '_form' => 'receipt',
        ])->assertRedirect(route('inventory.index'))
            ->assertSessionHas('success', 'Penerimaan stok berhasil dicatat.');

        $this->assertDatabaseHas('material_stocks', [
            'material_id' => $material->id,
            'on_hand' => '10.25',
        ]);
        $this->assertDatabaseHas('material_stock_movements', [
            'material_id' => $material->id,
            'movement_type' => MaterialStockMovement::TYPE_RECEIPT,
            'quantity' => '10.25',
            'notes' => 'Penerimaan awal',
        ]);

        $this->post(route('inventory.adjustments.store'), [
            'material_id' => $material->id,
            'on_hand' => '4.50',
            'notes' => 'Hasil hitung fisik',
            '_form' => 'adjustment',
        ])->assertRedirect(route('inventory.index'))
            ->assertSessionHas('success', 'Penyesuaian stok berhasil dicatat.');

        $this->assertDatabaseHas('material_stocks', [
            'material_id' => $material->id,
            'on_hand' => '4.50',
        ]);
        $this->assertDatabaseHas('material_stock_movements', [
            'material_id' => $material->id,
            'movement_type' => MaterialStockMovement::TYPE_ADJUSTMENT,
            'quantity' => '5.75',
        ]);
        $this->assertDatabaseCount('material_stock_movements', 2);

        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Penyesuaian turun: saldo 10.25 menjadi 4.50.')
            ->assertSee('Hasil hitung fisik');
    }

    public function test_receipts_and_adjustments_reject_invalid_values_without_negative_stock(): void
    {
        $material = Material::create([
            'sku' => 'MAT-STOCK-03',
            'name' => 'Resin',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $this->from(route('inventory.index'))
            ->post(route('inventory.receipts.store'), [
                'material_id' => $material->id,
                'quantity' => '0',
                '_form' => 'receipt',
            ])
            ->assertRedirect(route('inventory.index'))
            ->assertSessionHasErrors('quantity');

        $this->from(route('inventory.index'))
            ->post(route('inventory.adjustments.store'), [
                'material_id' => $material->id,
                'on_hand' => '-0.01',
                '_form' => 'adjustment',
            ])
            ->assertRedirect(route('inventory.index'))
            ->assertSessionHasErrors('on_hand');

        $this->from(route('inventory.index'))
            ->post(route('inventory.receipts.store'), [
                'material_id' => $material->id,
                'quantity' => '1.001',
                '_form' => 'receipt',
            ])
            ->assertRedirect(route('inventory.index'))
            ->assertSessionHasErrors('quantity');

        $this->from(route('inventory.index'))
            ->post(route('inventory.adjustments.store'), [
                'material_id' => $material->id,
                'on_hand' => '1.001',
                '_form' => 'adjustment',
            ])
            ->assertRedirect(route('inventory.index'))
            ->assertSessionHasErrors('on_hand');

        $this->assertDatabaseMissing('material_stocks', ['material_id' => $material->id]);
        $this->assertDatabaseCount('material_stock_movements', 0);
    }

    public function test_unchanged_physical_count_does_not_create_a_movement(): void
    {
        $material = Material::create([
            'sku' => 'MAT-STOCK-04',
            'name' => 'Packaging',
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $this->post(route('inventory.adjustments.store'), [
            'material_id' => $material->id,
            'on_hand' => '0.00',
            '_form' => 'adjustment',
        ])->assertRedirect(route('inventory.index'))
            ->assertSessionHas('success', 'Saldo stok tidak berubah; tidak ada movement yang perlu dicatat.');

        $this->assertDatabaseHas('material_stocks', [
            'material_id' => $material->id,
            'on_hand' => '0.00',
        ]);
        $this->assertDatabaseCount('material_stock_movements', 0);
    }

    public function test_receipt_that_exceeds_decimal_capacity_leaves_stock_and_history_unchanged(): void
    {
        $material = Material::create([
            'sku' => 'MAT-STOCK-06',
            'name' => 'High capacity material',
            'unit' => 'kg',
            'is_active' => true,
        ]);
        MaterialStock::create([
            'material_id' => $material->id,
            'on_hand' => '999999999999.99',
        ]);

        $this->from(route('inventory.index'))
            ->post(route('inventory.receipts.store'), [
                'material_id' => $material->id,
                'quantity' => '0.01',
                '_form' => 'receipt',
            ])
            ->assertRedirect(route('inventory.index'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('material_stocks', [
            'material_id' => $material->id,
            'on_hand' => '999999999999.99',
        ]);
        $this->assertDatabaseCount('material_stock_movements', 0);
    }

    public function test_reorder_level_can_be_set_and_material_with_movement_cannot_be_deleted(): void
    {
        $material = Material::create([
            'sku' => 'MAT-STOCK-05',
            'name' => 'Cardboard',
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $this->patch(route('inventory.reorder-level.update', $material), [
            'reorder_level' => '12.50',
            '_material_id' => (string) $material->id,
        ])->assertRedirect(route('inventory.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('material_stocks', [
            'material_id' => $material->id,
            'on_hand' => '0.00',
            'reorder_level' => '12.50',
        ]);
        $this->assertTrue($material->stock()->firstOrFail()->isAtOrBelowReorderLevel());

        $this->post(route('inventory.receipts.store'), [
            'material_id' => $material->id,
            'quantity' => '3.00',
            '_form' => 'receipt',
        ])->assertRedirect(route('inventory.index'));

        $this->delete(route('materials.destroy', $material))
            ->assertRedirect(route('materials.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('materials', ['id' => $material->id]);
        $this->assertDatabaseHas('material_stock_movements', ['material_id' => $material->id]);
    }
}
