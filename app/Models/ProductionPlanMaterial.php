<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionPlanMaterial extends Model
{
    protected $fillable = [
        'production_plan_id',
        'material_id',
        'required_quantity',
    ];

    protected function casts(): array
    {
        return [
            'required_quantity' => 'decimal:2',
        ];
    }

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
