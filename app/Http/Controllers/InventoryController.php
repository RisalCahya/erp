<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\StoreStockReceiptRequest;
use App\Http\Requests\UpdateStockReorderLevelRequest;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Services\MaterialInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $materials = Material::query()
            ->with('stock')
            ->orderBy('name')
            ->paginate(10, ['*'], 'materials_page')
            ->withQueryString();
        $materialOptions = Material::query()->orderBy('name')->get();
        $movements = MaterialStockMovement::query()
            ->with(['material', 'reference'])
            ->latest()
            ->paginate(10, ['*'], 'movements_page')
            ->withQueryString();

        return view('inventory.index', compact('materials', 'materialOptions', 'movements'));
    }

    public function storeReceipt(
        StoreStockReceiptRequest $request,
        MaterialInventoryService $inventory,
    ): RedirectResponse {
        $material = Material::findOrFail($request->validated('material_id'));

        $inventory->receive(
            $material,
            (string) $request->validated('quantity'),
            $request->validated('notes'),
        );

        return redirect()
            ->route('inventory.index')
            ->with('success', 'Penerimaan stok berhasil dicatat.');
    }

    public function storeAdjustment(
        StoreStockAdjustmentRequest $request,
        MaterialInventoryService $inventory,
    ): RedirectResponse {
        $material = Material::findOrFail($request->validated('material_id'));
        $movement = $inventory->adjustTo(
            $material,
            (string) $request->validated('on_hand'),
            $request->validated('notes'),
        );

        return redirect()
            ->route('inventory.index')
            ->with('success', $movement
                ? 'Penyesuaian stok berhasil dicatat.'
                : 'Saldo stok tidak berubah; tidak ada movement yang perlu dicatat.');
    }

    public function updateReorderLevel(
        UpdateStockReorderLevelRequest $request,
        Material $material,
        MaterialInventoryService $inventory,
    ): RedirectResponse {
        $reorderLevel = $request->validated('reorder_level');
        $inventory->setReorderLevel($material, $reorderLevel === null ? null : (string) $reorderLevel);

        return redirect()
            ->route('inventory.index')
            ->with('success', 'Stok minimum berhasil diperbarui.');
    }
}
