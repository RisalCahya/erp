<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductionRealizationRequest extends FormRequest
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
            'transaction_date' => ['required', 'date'],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'finished_good_quantity' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:999999999999.99',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('product_id')) {
                    return;
                }

                $product = Product::query()
                    ->whereKey($this->input('product_id'))
                    ->where('is_active', true)
                    ->first();

                if ($product && ! filled($product->output_unit)) {
                    $validator->errors()->add(
                        'product_id',
                        'Satuan output produk belum diatur pada Master Produk.',
                    );
                }

                if ($product && ! $product->bomItems()->exists()) {
                    $validator->errors()->add('product_id', 'Produk ini belum memiliki BOM material.');
                }
            },
        ];
    }
}
