<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\ProductionRealization;
use App\Services\MaterialRequirementService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(MaterialRequirementService $requirements): View
    {
        $plannedReport = $requirements->plannedRequirementsReport();
        $shortageMaterials = $plannedReport['requirements']
            ->where('status', 'kurang')
            ->take(8)
            ->values();
        $upcomingPlans = ProductionPlan::query()
            ->where('status', ProductionPlan::STATUS_PLANNED)
            ->with('product')
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit(6)
            ->get();
        $planShortageCounts = $requirements->shortageCountsForPlans($upcomingPlans);
        $recentRealizations = ProductionRealization::query()
            ->with('product')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        return view('dashboard', [
            'activeProductCount' => Product::query()->where('is_active', true)->count(),
            'activeMaterialCount' => Material::query()->where('is_active', true)->count(),
            'plannedPlanCount' => ProductionPlan::query()->where('status', ProductionPlan::STATUS_PLANNED)->count(),
            'shortageMaterialCount' => $plannedReport['requirements']->where('status', 'kurang')->count(),
            'shortageMaterials' => $shortageMaterials,
            'upcomingPlans' => $upcomingPlans,
            'planShortageCounts' => $planShortageCounts,
            'recentRealizations' => $recentRealizations,
        ]);
    }
}
