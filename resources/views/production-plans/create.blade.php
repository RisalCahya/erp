@extends('layouts.app')

@section('title', 'Buat Rencana Produksi')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('production-plans.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-700">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
            Kembali ke Rencana Produksi
        </a>
        <div class="mt-5">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Perencanaan</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Buat Rencana Produksi</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Kebutuhan dihitung dari BOM aktif dan disimpan sebagai snapshot. Stok tidak dikurangi saat rencana dibuat.</p>
        </div>

        @if ($products->isEmpty())
            <section class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                <h2 class="font-semibold">Belum ada produk yang siap direncanakan</h2>
                <p class="mt-1 leading-6">Aktifkan produk dan tambahkan setidaknya satu material ke BOM sebelum membuat rencana produksi.</p>
                <a href="{{ route('bom.index') }}" class="mt-3 inline-flex font-semibold text-blue-800 underline decoration-blue-300 underline-offset-2">Buka BOM</a>
            </section>
        @else
            @php
                $oldProduct = $products->firstWhere('id', old('product_id'));
            @endphp
            <form method="POST" action="{{ route('production-plans.store') }}" class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" data-product-combobox>
                @csrf
                <div class="space-y-5 p-5 sm:p-7">
                    <div class="border-b border-slate-100 pb-4">
                        <h2 class="text-sm font-semibold text-slate-900">Detail Rencana</h2>
                        <p class="mt-1 text-xs text-slate-500">Nomor rencana akan dibuat otomatis setelah data tersimpan.</p>
                    </div>

                    <div>
                        <label for="product-search" class="mb-1.5 block text-sm font-semibold text-slate-800">Produk <span class="text-red-600">*</span></label>
                        <input id="product_id" type="hidden" name="product_id" value="{{ old('product_id') }}" data-product-id>
                        <div class="relative">
                            <input id="product-search" type="search" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="production-plan-product-options" aria-expanded="false"
                                   value="{{ $oldProduct ? $oldProduct->sku.' — '.$oldProduct->name : '' }}"
                                   placeholder="Cari SKU atau nama produk..." data-product-search
                                   @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('product_id'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('product_id')])>
                            <div id="production-plan-product-options" role="listbox" class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg" data-product-options>
                            @foreach ($products as $product)
                                <button type="button" role="option" aria-selected="{{ (string) old('product_id') === (string) $product->id ? 'true' : 'false' }}"
                                        data-product-option data-id="{{ $product->id }}"
                                        data-label="{{ $product->sku }} — {{ $product->name }}"
                                        data-search="{{ \Illuminate\Support\Str::lower($product->sku.' '.$product->name) }}"
                                        class="flex w-full items-center justify-between rounded-md px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-blue-50 hover:text-blue-800 focus:bg-blue-50 focus:outline-none">
                                    <span><span class="font-mono text-xs font-semibold">{{ $product->sku }}</span><span class="ml-2">{{ $product->name }}</span></span>
                                </button>
                            @endforeach
                            </div>
                        </div>
                        @error('product_id') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-xs text-slate-500">Cari berdasarkan SKU atau nama. Daftar hanya menampilkan produk aktif yang sudah memiliki BOM.</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="planned_quantity" class="mb-1.5 block text-sm font-semibold text-slate-800">Jumlah Produksi <span class="text-red-600">*</span></label>
                            <input id="planned_quantity" name="planned_quantity" type="number" min="0.01" step="0.01" value="{{ old('planned_quantity') }}" required placeholder="Contoh: 120.00"
                                   @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('planned_quantity'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('planned_quantity')])>
                            @error('planned_quantity') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                            <p class="mt-1.5 text-xs text-slate-500">Maksimal 2 angka desimal.</p>
                        </div>
                        <div>
                            <label for="due_date" class="mb-1.5 block text-sm font-semibold text-slate-800">Tanggal Dibutuhkan <span class="text-red-600">*</span></label>
                            <input id="due_date" name="due_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('due_date') }}" required
                                   @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('due_date'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('due_date')])>
                            @error('due_date') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
                    <a href="{{ route('production-plans.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">Buat Rencana</button>
                </div>
            </form>
        @endif
    </div>
@endsection
