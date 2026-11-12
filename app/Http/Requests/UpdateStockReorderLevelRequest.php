<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockReorderLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reorder_level' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:999999999999.99'],
        ];
    }
}
