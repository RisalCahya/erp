@extends('layouts.app')

@section('title', 'Buat Realisasi Produksi')

@section('content')
    <div class="mb-5">
        <a href="{{ route('production-realizations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-700">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
            Kembali ke Realisasi Produksi
        </a>
    </div>

    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Transaksi Produksi</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Realisasi Barang Jadi</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Simpan sebagai draft untuk melihat formula. Stok baru berubah setelah draft diposting sebagai realisasi.</p>
    </div>

    <form method="POST" action="{{ route('production-realizations.store') }}"
          data-production-realization-form
          data-product-combobox
          data-preview-url-template="{{ $previewUrlTemplate }}"
          class="space-y-6">
        @csrf
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-7">
                <h2 class="text-sm font-semibold text-slate-900">Informasi Transaksi</h2>
                <p class="mt-1 text-xs text-slate-500">Nomor transaksi unik akan dibuat otomatis saat draft disimpan.</p>
            </div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-7">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-800">Nomor Transaksi</label>
                    <div class="flex min-h-11 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-500">Otomatis saat simpan draft</div>
                </div>
                <div>
                    <label for="transaction_date" class="mb-1.5 block text-sm font-semibold text-slate-800">Tanggal Transaksi <span class="text-red-600">*</span></label>
                    <input id="transaction_date" name="transaction_date" type="date" value="{{ old('transaction_date', now()->toDateString()) }}" required
                           @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('transaction_date'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('transaction_date')])>
                    @error('transaction_date') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="product_search" class="mb-1.5 block text-sm font-semibold text-slate-800">Produk Jadi <span class="text-red-600">*</span></label>
                    <div class="relative">
                        <input id="product_search" type="search" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="realization-product-options" aria-expanded="false" placeholder="Cari dengan SKU atau nama produk"
                               data-product-search
                               @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('product_id'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('product_id')])>
                        <div id="realization-product-options" role="listbox" class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg" data-product-options>
                            @foreach ($products as $product)
                                <button type="button" role="option" aria-selected="false"
                                        data-product-option data-id="{{ $product->id }}"
                                        data-sku="{{ $product->sku }}"
                                        data-name="{{ $product->name }}"
                                        data-unit="{{ $product->output_unit }}"
                                        data-label="{{ $product->sku }} — {{ $product->name }}"
                                        data-search="{{ \Illuminate\Support\Str::lower($product->sku.' '.$product->name) }}"
                                        class="flex w-full items-center justify-between rounded-md px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-blue-50 hover:text-blue-800 focus:bg-blue-50 focus:outline-none">
                                    <span><span class="font-mono text-xs font-semibold">{{ $product->sku }}</span><span class="ml-2">{{ $product->name }}</span></span>
                                    <span class="ml-3 shrink-0 text-xs text-slate-500">{{ $product->output_unit ?: 'Satuan belum diatur' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="product_id" value="{{ old('product_id') }}" data-product-id>
                    @error('product_id') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                    @if ($products->isEmpty())
                        <p class="mt-1.5 text-xs text-amber-700">Belum ada produk aktif. Tambahkan produk dan satuan output pada Master Produk.</p>
                    @else
                        <p class="mt-1.5 text-xs text-slate-500">Pilih hasil yang cocok dari daftar SKU dan nama produk aktif.</p>
                    @endif
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-800">Produk Terpilih</label>
                    <div class="min-h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700" data-product-summary>Produk belum dipilih</div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-800">Satuan Output Produk</label>
                    <div class="min-h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700" data-output-unit></div>
                </div>
                <div>
                    <label for="finished_good_quantity" class="mb-1.5 block text-sm font-semibold text-slate-800">Qty Finished Good <span class="text-red-600">*</span></label>
                    <input id="finished_good_quantity" name="finished_good_quantity" type="number" min="0.01" max="999999999999.99" step="0.01" value="{{ old('finished_good_quantity') }}" required placeholder="Contoh: 500.00" data-finished-good-quantity
                           @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('finished_good_quantity'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('finished_good_quantity')])>
                    @error('finished_good_quantity') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-xs text-slate-500">Jumlah output barang jadi; satuan mengikuti Master Produk. Maksimal 2 angka desimal.</p>
                </div>
                <div>
                    <label for="notes" class="mb-1.5 block text-sm font-semibold text-slate-800">Keterangan <span class="font-normal text-slate-500">(opsional)</span></label>
                    <input id="notes" name="notes" type="text" maxlength="5000" value="{{ old('notes') }}" placeholder="Catatan transaksi"
                           class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">
                    @error('notes') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-7">
                <h2 class="text-sm font-semibold text-slate-900">Formula dari BOM</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">Qty BOM adalah kebutuhan material per satu unit output. Qty. Pakai dihitung ulang saat Qty Finished Good berubah; data BOM tidak diubah.</p>
            </div>
            <p class="hidden mx-5 mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:mx-7" data-formula-message></p>
            <div class="overflow-x-auto p-0">
                <table class="w-full min-w-[1250px] divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">SKU Material</th>
                            <th class="px-4 py-3">Nama Material</th>
                            <th class="px-4 py-3">Satuan Material</th>
                            <th class="px-4 py-3 text-right">Qty BOM / Output</th>
                            <th class="px-4 py-3 text-right">Qty Finished Good</th>
                            <th class="px-4 py-3 text-right">Qty. Pakai</th>
                            <th class="px-4 py-3 text-right">Stok Tersedia</th>
                            <th class="px-4 py-3 text-right">Kekurangan</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" data-formula-body>
                        <tr><td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">Pilih produk untuk memuat formula BOM.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
                <a href="{{ route('production-realizations.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
                <button type="submit" data-save-draft disabled class="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">Simpan Draft</button>
            </div>
        </section>
    </form>
@endsection
