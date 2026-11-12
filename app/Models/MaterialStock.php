<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialStock extends Model
{
    protected $fillable = [
        'material_id',
        'on_hand',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'on_hand' => 'decimal:2',
            'reorder_level' => 'decimal:2',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(MaterialStockMovement::class, 'material_id', 'material_id');
    }

    public function isAtOrBelowReorderLevel(): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }

        [$onHandWhole, $onHandFraction] = array_pad(explode('.', $this->on_hand, 2), 2, '0');
        [$reorderWhole, $reorderFraction] = array_pad(explode('.', $this->reorder_level, 2), 2, '0');
        $onHandWhole = ltrim($onHandWhole, '0') ?: '0';
        $reorderWhole = ltrim($reorderWhole, '0') ?: '0';

        if (strlen($onHandWhole) !== strlen($reorderWhole)) {
            return strlen($onHandWhole) < strlen($reorderWhole);
        }

        $wholeComparison = strcmp($onHandWhole, $reorderWhole);

        if ($wholeComparison !== 0) {
            return $wholeComparison < 0;
        }

        return strcmp(
            str_pad(substr($onHandFraction, 0, 2), 2, '0'),
            str_pad(substr($reorderFraction, 0, 2), 2, '0'),
        ) <= 0;
    }
}
