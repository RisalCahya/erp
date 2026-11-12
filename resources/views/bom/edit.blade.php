@extends('layouts.app')

@section('title', 'Edit BOM')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('bom.index', ['product_id' => $bomItem->product_id]) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-700">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
            Kembali ke BOM
        </a>
        <div class="mt-5">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Bill of Materials</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Edit Kuantitas Material</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                {{ $bomItem->product->name }} membutuhkan {{ $bomItem->material->name }} ({{ $bomItem->material->unit }}) untuk setiap unit produk.
            </p>
        </div>

        <form method="POST" action="{{ route('bom-items.update', $bomItem) }}" class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @method('PUT')
            <div class="space-y-5 p-5 sm:p-7">
                <div>
                    <label for="quantity_per_product" class="mb-1.5 block text-sm font-semibold text-slate-800">Kuantitas per Produk <span class="text-red-600">*</span></label>
                    <div class="flex items-center gap-3">
                        <input id="quantity_per_product" name="quantity_per_product" type="number" min="0.01" step="0.01"
                               value="{{ old('quantity_per_product', $bomItem->quantity_per_product) }}" required
                               @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('quantity_per_product'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('quantity_per_product')])>
                        <span class="shrink-0 text-sm font-medium text-slate-600">{{ $bomItem->material->unit }}</span>
                    </div>
                    @error('quantity_per_product') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-xs text-slate-500">Masukkan angka lebih besar dari nol dengan maksimal 2 angka desimal.</p>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
                <a href="{{ route('bom.index', ['product_id' => $bomItem->product_id]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection
