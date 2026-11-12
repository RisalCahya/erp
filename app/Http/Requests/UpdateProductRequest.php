<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
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
        return [
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($this->route('product')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'output_unit' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
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

                if (
                    $validator->errors()->has('output_unit')
                    || ! $product instanceof Product
                    || $this->input('output_unit') === $product->output_unit
                ) {
                    return;
                }

                if ($product->productionRealizations()->exists()) {
                    $validator->errors()->add(
                        'output_unit',
                        'Satuan output tidak dapat diubah setelah produk memiliki transaksi realisasi.',
                    );
                }
            },
        ];
    }
}
