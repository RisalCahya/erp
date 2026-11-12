<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBomItemRequest;
use App\Http\Requests\UpdateBomItemRequest;
use App\Models\BomItem;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BomItemController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $selectedProduct = isset($validated['product_id'])
            ? Product::findOrFail($validated['product_id'])
            : null;

        $products = Product::query()->orderBy('name')->get();
        $materials = Material::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $bomItems = $selectedProduct
            ? $selectedProduct->bomItems()->with('material')->orderBy('id')->get()
            : collect();

        return view('bom.index', compact('products', 'materials', 'selectedProduct', 'bomItems'));
    }

    public function store(StoreBomItemRequest $request, Product $product): RedirectResponse
    {
        $product->bomItems()->create($request->validated());

        return redirect()
            ->route('bom.index', ['product_id' => $product->id])
            ->with('success', 'Material berhasil ditambahkan ke BOM.');
    }

    public function edit(BomItem $bomItem): View
    {
        $bomItem->load(['product', 'material']);

        return view('bom.edit', compact('bomItem'));
    }

    public function update(UpdateBomItemRequest $request, BomItem $bomItem): RedirectResponse
    {
        $bomItem->update($request->validated());

        return redirect()
            ->route('bom.index', ['product_id' => $bomItem->product_id])
            ->with('success', 'Kuantitas material pada BOM berhasil diperbarui.');
    }

    public function destroy(BomItem $bomItem): RedirectResponse
    {
        $productId = $bomItem->product_id;
        $bomItem->delete();

        return redirect()
            ->route('bom.index', ['product_id' => $productId])
            ->with('success', 'Baris material berhasil dihapus dari BOM.');
    }
}
