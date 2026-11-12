<?php

namespace Tests\Feature;

use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApplicationUsers;
use Tests\TestCase;

class MaterialCrudTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApplicationUsers;

    public function test_material_can_be_created_listed_viewed_updated_and_deleted(): void
    {
        $this->post(route('materials.store'), [
            'sku' => 'MAT-100',
            'name' => 'Steel sheet',
            'unit' => 'kg',
            'is_active' => '1',
        ])->assertRedirect(route('materials.index'))
            ->assertSessionHas('success');

        $material = Material::query()->where('sku', 'MAT-100')->firstOrFail();

        $this->get(route('materials.create'))->assertOk();
        $this->get(route('materials.index', ['search' => 'Steel']))
            ->assertOk()
            ->assertSee('MAT-100');

        $this->get(route('materials.show', $material))
            ->assertOk()
            ->assertSee('kg');
        $this->get(route('materials.edit', $material))->assertOk();

        $this->put(route('materials.update', $material), [
            'sku' => 'MAT-100',
            'name' => 'Steel sheet premium',
            'unit' => 'liter',
            'is_active' => '0',
        ])->assertRedirect(route('materials.show', $material))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'name' => 'Steel sheet premium',
            'unit' => 'liter',
            'is_active' => false,
        ]);

        $this->delete(route('materials.destroy', $material))
            ->assertRedirect(route('materials.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
    }

    public function test_material_validation_rejects_duplicate_sku_and_missing_required_fields(): void
    {
        Material::create([
            'sku' => 'MAT-100',
            'name' => 'Existing material',
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $this->from(route('materials.create'))
            ->post(route('materials.store'), [
                'sku' => 'MAT-100',
                'name' => '',
                'unit' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('materials.create'))
            ->assertSessionHasErrors(['sku', 'name', 'unit']);
    }
}
