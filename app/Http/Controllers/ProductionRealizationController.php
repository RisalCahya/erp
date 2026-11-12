<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductionRealizationRequest;
use App\Models\Product;
use App\Models\ProductionRealization;
use App\Services\ProductionRealizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionRealizationController extends Controller
{
    public function index(): View
    {
        $realizations = ProductionRealization::query()
            ->with('product')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('production-realizations.index', compact('realizations'));
    }

    public function create(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $previewUrlTemplate = route('production-realizations.products.preview', ['product' => '__PRODUCT__']);

        return view('production-realizations.create', compact('products', 'previewUrlTemplate'));
    }

    public function store(
        StoreProductionRealizationRequest $request,
        ProductionRealizationService $realizations,
    ): RedirectResponse {
        $realization = $realizations->createDraft($request->validated());

        return redirect()
            ->route('production-realizations.show', $realization)
            ->with('success', 'Draft realisasi produksi berhasil disimpan. Stok belum berubah.');
    }

    public function preview(
        Request $request,
        Product $product,
        ProductionRealizationService $realizations,
    ): JsonResponse {
        abort_unless($product->is_active, 404);

        $validated = $request->validate([
            'finished_good_quantity' => [
                'nullable',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:999999999999.99',
            ],
        ]);
        $preview = $realizations->preview($product, $validated['finished_good_quantity'] ?? null);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'output_unit' => $product->output_unit,
            ],
            'bom_items' => $preview['bom_items'],
            'message' => $preview['message'],
        ]);
    }

    public function show(ProductionRealization $productionRealization): View
    {
        $productionRealization->load([
            'product.finishedGoodStock',
            'materials.material',
            'finishedGoodMovement',
        ]);

        return view('production-realizations.show', compact('productionRealization'));
    }

    public function post(
        ProductionRealization $productionRealization,
        ProductionRealizationService $realizations,
    ): RedirectResponse {
        $realizations->post($productionRealization);

        return redirect()
            ->route('production-realizations.show', $productionRealization)
            ->with('success', 'Realisasi produksi berhasil diposting. Stok material dan barang jadi telah diperbarui.');
    }
}
