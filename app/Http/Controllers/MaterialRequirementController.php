<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\MaterialRequirementService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class MaterialRequirementController extends Controller
{
    public function index(Request $request, MaterialRequirementService $requirements): View
    {
        $filters = $request->validate([
            'due_date_from' => ['nullable', 'date'],
            'due_date_to' => ['nullable', 'date', 'after_or_equal:due_date_from'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);
        $report = $requirements->plannedRequirementsReport($filters);
        $allRequirements = $report['requirements'];
        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $materialRequirements = new LengthAwarePaginator(
            $allRequirements->forPage($page, $perPage)->values(),
            $allRequirements->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ],
        );
        $products = Product::query()->orderBy('name')->get();
        $selectedProduct = $products->firstWhere('id', (int) ($filters['product_id'] ?? 0));
        $shortageCount = $allRequirements->where('status', 'kurang')->count();

        return view('material-requirements.index', [
            'materialRequirements' => $materialRequirements,
            'materialCount' => $allRequirements->count(),
            'sufficientCount' => $allRequirements->where('status', 'cukup')->count(),
            'shortageCount' => $shortageCount,
            'planCount' => $report['plan_count'],
            'products' => $products,
            'selectedProduct' => $selectedProduct,
            'filters' => $filters,
        ]);
    }
}
