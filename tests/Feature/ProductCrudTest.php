<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApplicationUsers;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApplicationUsers;

    public function test_product_can_be_created_listed_viewed_updated_and_deleted(): void
    {
        $this->post(route('products.store'), [
            'sku' => 'PRD-100',
            'name' => 'Widget',
            'output_unit' => 'pcs',
            'description' => 'Produk simulasi',
            'is_active' => '1',
        ])->assertRedirect(route('products.index'))
            ->assertSessionHas('success');

        $product = Product::query()->where('sku', 'PRD-100')->firstOrFail();

        $this->get(route('products.create'))->assertOk();
        $this->get(route('products.index', ['search' => 'Widget']))
            ->assertOk()
            ->assertSee('PRD-100');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Produk simulasi');
        $this->get(route('products.edit', $product))->assertOk();

        $this->put(route('products.update', $product), [
            'sku' => 'PRD-100',
            'name' => 'Widget Plus',
            'output_unit' => 'dus',
            'description' => '',
            'is_active' => '0',
        ])->assertRedirect(route('products.show', $product))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Widget Plus',
            'output_unit' => 'dus',
            'description' => null,
            'is_active' => false,
        ]);

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_validation_rejects_duplicate_sku_and_missing_required_fields(): void
    {
        Product::create([
            'sku' => 'PRD-100',
            'name' => 'Existing product',
            'is_active' => true,
        ]);

        $this->from(route('products.create'))
            ->post(route('products.store'), [
                'sku' => 'PRD-100',
                'name' => '',
                'output_unit' => 'pcs',
                'is_active' => '1',
            ])
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['sku', 'name']);
    }

    public function test_product_search_can_return_no_matches(): void
    {
        Product::create([
            'sku' => 'PRD-100',
            'name' => 'Widget',
            'is_active' => true,
        ]);

        $this->get(route('products.index', ['search' => 'no-result']))
            ->assertOk()
            ->assertSee('Produk tidak ditemukan');
    }
}
