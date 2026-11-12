<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductionPlanRequest;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Services\MaterialRequirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductionPlanController extends Controller
{
    public function index(): View
    {
        $productionPlans = ProductionPlan::query()
            ->with('product')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('production-plans.index', compact('productionPlans'));
    }

    public function create(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->whereHas('bomItems')
            ->orderBy('name')
            ->get();

        return view('production-plans.create', compact('products'));
    }

    public function store(
        StoreProductionPlanRequest $request,
        MaterialRequirementService $requirements,
    ): RedirectResponse {
        $plan = $requirements->createPlannedPlan($request->validated());

        return redirect()
            ->route('production-plans.show', $plan)
            ->with('success', 'Rencana produksi dan snapshot kebutuhan material berhasil dibuat.');
    }

    public function show(
        ProductionPlan $productionPlan,
        MaterialRequirementService $requirements,
    ): View {
        $productionPlan->load('product');
        $planRequirements = $requirements->requirementsForPlan($productionPlan);
        $shortageCount = $planRequirements->where('status', 'kurang')->count();

        return view('production-plans.show', compact(
            'productionPlan',
            'planRequirements',
            'shortageCount',
        ));
    }

    public function destroy(ProductionPlan $productionPlan): RedirectResponse
    {
        $productionPlan->delete();

        return redirect()
            ->route('production-plans.index')
            ->with('success', 'Rencana produksi dan snapshot kebutuhan material berhasil dihapus.');
    }
}
