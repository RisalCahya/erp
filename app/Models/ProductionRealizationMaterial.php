<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRealizationMaterial extends Model
{
    protected $fillable = [
        'production_realization_id',
        'material_id',
        'quantity_per_output',
        'required_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity_per_output' => 'decimal:2',
            'required_quantity' => 'decimal:2',
        ];
    }

    public function productionRealization(): BelongsTo
    {
        return $this->belongsTo(ProductionRealization::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
