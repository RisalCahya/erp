<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialStockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaterialInventoryService
{
    private const MAX_UNITS = 99_999_999_999_999;

    private const UNITS_PER_QUANTITY = 100;

    public function receive(Material $material, string $quantity, ?string $notes = null): MaterialStockMovement
    {
        return DB::transaction(function () use ($material, $quantity, $notes): MaterialStockMovement {
            $stock = $this->lockStock($material);
            $quantityUnits = $this->toUnits($quantity);
            $currentUnits = $this->toUnits($stock->on_hand);

            if ($quantityUnits <= 0 || $quantityUnits > self::MAX_UNITS - $currentUnits) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah penerimaan melebihi kapasitas stok yang dapat disimpan.',
                ]);
            }

            $stock->update(['on_hand' => $this->fromUnits($currentUnits + $quantityUnits)]);

            return $stock->movements()->create([
                'movement_type' => MaterialStockMovement::TYPE_RECEIPT,
                'quantity' => $this->fromUnits($quantityUnits),
                'notes' => $notes,
            ]);
        }, attempts: 3);
    }

    public function adjustTo(Material $material, string $targetOnHand, ?string $notes = null): ?MaterialStockMovement
    {
        return DB::transaction(function () use ($material, $targetOnHand, $notes): ?MaterialStockMovement {
            $stock = $this->lockStock($material);
            $targetUnits = $this->toUnits($targetOnHand);
            $currentUnits = $this->toUnits($stock->on_hand);

            if ($targetUnits < 0 || $targetUnits > self::MAX_UNITS) {
                throw ValidationException::withMessages([
                    'on_hand' => 'Saldo stok harus berada dalam rentang yang dapat disimpan.',
                ]);
            }

            if ($targetUnits === $currentUnits) {
                return null;
            }

            $direction = $targetUnits > $currentUnits ? 'naik' : 'turun';
            $systemNote = sprintf(
                'Penyesuaian %s: saldo %s menjadi %s.',
                $direction,
                $this->fromUnits($currentUnits),
                $this->fromUnits($targetUnits),
            );
            $movementNotes = trim($systemNote.($notes ? ' '.$notes : ''));

            $stock->update(['on_hand' => $this->fromUnits($targetUnits)]);

            return $stock->movements()->create([
                'movement_type' => MaterialStockMovement::TYPE_ADJUSTMENT,
                'quantity' => $this->fromUnits(abs($targetUnits - $currentUnits)),
                'notes' => $movementNotes,
            ]);
        }, attempts: 3);
    }

    public function setReorderLevel(Material $material, ?string $reorderLevel): MaterialStock
    {
        return DB::transaction(function () use ($material, $reorderLevel): MaterialStock {
            $stock = $this->lockStock($material);
            $stock->update(['reorder_level' => $reorderLevel]);

            return $stock;
        }, attempts: 3);
    }

    private function lockStock(Material $material): MaterialStock
    {
        $material->stock()->firstOrCreate([], ['on_hand' => '0.00']);

        return $material->stock()->lockForUpdate()->firstOrFail();
    }

    private function toUnits(string $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '0');

        return ((int) $whole * self::UNITS_PER_QUANTITY) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromUnits(int $units): string
    {
        return sprintf('%d.%02d', intdiv($units, self::UNITS_PER_QUANTITY), $units % self::UNITS_PER_QUANTITY);
    }
}
