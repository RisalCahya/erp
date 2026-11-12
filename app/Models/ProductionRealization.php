<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductionRealization extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'product_id',
        'finished_good_quantity',
        'output_unit',
        'notes',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'finished_good_quantity' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionRealizationMaterial::class);
    }

    public function finishedGoodMovement(): HasOne
    {
        return $this->hasOne(FinishedGoodStockMovement::class);
    }
}
