<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Material;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $materials = Material::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('materials.index', compact('materials', 'search'));
    }

    public function create(): View
    {
        return view('materials.create', ['material' => new Material]);
    }

    public function store(StoreMaterialRequest $request): RedirectResponse
    {
        Material::create($request->validated());

        return redirect()
            ->route('materials.index')
            ->with('success', 'Material berhasil ditambahkan.');
    }

    public function show(Material $material): View
    {
        return view('materials.show', compact('material'));
    }

    public function edit(Material $material): View
    {
        return view('materials.edit', compact('material'));
    }

    public function update(UpdateMaterialRequest $request, Material $material): RedirectResponse
    {
        $material->update($request->validated());

        return redirect()
            ->route('materials.show', $material)
            ->with('success', 'Material berhasil diperbarui.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        if (
            $material->stockMovements()->exists()
            || $material->bomItems()->exists()
            || $material->productionPlanMaterials()->exists()
            || $material->productionRealizationMaterials()->exists()
        ) {
            return redirect()
                ->route('materials.index')
                ->with('error', 'Material tidak dapat dihapus karena sudah digunakan pada BOM, rencana, realisasi, atau riwayat stok.');
        }

        $material->delete();

        return redirect()
            ->route('materials.index')
            ->with('success', 'Material berhasil dihapus.');
    }
}
