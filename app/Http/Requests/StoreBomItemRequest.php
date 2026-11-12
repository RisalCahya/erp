<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBomItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'material_id' => [
                'required',
                'integer',
                Rule::exists('materials', 'id')->where('is_active', true),
                Rule::unique('bom_items', 'material_id')
                    ->where('product_id', $product instanceof Product ? $product->getKey() : null),
            ],
            'quantity_per_product' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $product = $this->route('product');

                if ($product instanceof Product && ! $product->is_active) {
                    $validator->errors()->add('product', 'Produk nonaktif tidak dapat digunakan untuk baris BOM baru.');
                }
            },
        ];
    }
}
