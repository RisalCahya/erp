<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinishedGoodStockMovement extends Model
{
    public const TYPE_PRODUCTION_OUTPUT = 'production_output';

    protected $fillable = [
        'product_id',
        'production_realization_id',
        'movement_type',
        'quantity',
        'unit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productionRealization(): BelongsTo
    {
        return $this->belongsTo(ProductionRealization::class);
    }
}
