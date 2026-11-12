<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinishedGoodStock extends Model
{
    protected $fillable = [
        'product_id',
        'unit',
        'on_hand',
    ];

    protected function casts(): array
    {
        return [
            'on_hand' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FinishedGoodStockMovement::class, 'product_id', 'product_id');
    }
}
