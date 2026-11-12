@extends('layouts.app')

@section('title', 'BOM')

@section('content')
    <div class="mb-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Data engineering</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Bill of Materials</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Kelola daftar material dan kuantitas yang diperlukan untuk membuat satu unit produk.</p>
    </div>

    <section class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <form method="GET" action="{{ route('bom.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-start" data-product-combobox data-combobox-optional>
            <div class="w-full sm:max-w-lg">
                <label for="bom-product-search" class="mb-1.5 block text-sm font-semibold text-slate-800">Pilih Produk</label>
                <input id="bom-product-id" type="hidden" name="product_id" value="{{ optional($selectedProduct)->id }}" data-product-id>
                <div class="relative">
                    <input id="bom-product-search" type="search" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="bom-product-options" aria-expanded="false"
                           value="{{ $selectedProduct ? $selectedProduct->sku.' — '.$selectedProduct->name.(!$selectedProduct->is_active ? ' (Nonaktif)' : '') : '' }}"
                           placeholder="Cari SKU atau nama produk..." data-product-search
                           class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">
                    <div id="bom-product-options" role="listbox" class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg" data-product-options>
                    @foreach ($products as $product)
                        <button type="button" role="option" aria-selected="{{ optional($selectedProduct)->id === $product->id ? 'true' : 'false' }}"
                                data-product-option data-id="{{ $product->id }}"
                                data-label="{{ $product->sku }} — {{ $product->name }}{{ $product->is_active ? '' : ' (Nonaktif)' }}"
                                data-search="{{ \Illuminate\Support\Str::lower($product->sku.' '.$product->name.(!$product->is_active ? ' nonaktif' : ' aktif')) }}"
                                class="flex w-full items-center justify-between rounded-md px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-blue-50 hover:text-blue-800 focus:bg-blue-50 focus:outline-none">
                            <span><span class="font-mono text-xs font-semibold">{{ $product->sku }}</span><span class="ml-2">{{ $product->name }}</span></span>
                            @unless ($product->is_active)<span class="ml-3 shrink-0 text-xs text-slate-500">Nonaktif</span>@endunless
                        </button>
                    @endforeach
                    </div>
                </div>
                @error('product_id') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                <p class="mt-1.5 text-xs text-slate-500">Cari berdasarkan SKU atau nama produk.</p>
            </div>
            <div class="sm:pt-0">
                <div aria-hidden="true" class="mb-1.5 h-5"></div>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100">
                    Tampilkan BOM
                </button>
            </div>
        </form>
    </section>

    @if (! $selectedProduct)
        <section class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                <svg aria-hidden="true" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
            </span>
            <h2 class="mt-3 text-sm font-semibold text-slate-900">Pilih produk terlebih dahulu</h2>
            <p class="mt-1 text-sm text-slate-500">Daftar material penyusun akan ditampilkan setelah produk dipilih.</p>
        </section>
    @else
        <section class="mb-5 flex flex-col gap-3 rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">BOM Produk</p>
                <h2 class="mt-1 text-lg font-bold text-slate-950">{{ $selectedProduct->name }}</h2>
                <p class="mt-0.5 font-mono text-xs text-slate-600">{{ $selectedProduct->sku }}</p>
            </div>
            @if (! $selectedProduct->is_active)
                <p role="status" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                    Produk nonaktif. BOM dapat dilihat dan diperbarui, tetapi material baru tidak dapat ditambahkan.
                </p>
            @endif
        </section>

        @if ($selectedProduct->is_active)
            <section class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-4">
                    <h2 class="text-sm font-semibold text-slate-900">Tambah Material</h2>
                    <p class="mt-1 text-xs text-slate-500">Pilih material aktif dan tentukan kebutuhan untuk satu unit produk.</p>
                </div>

                @if ($materials->isEmpty())
                    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
                        Belum ada material aktif yang dapat ditambahkan. Aktifkan atau tambahkan material pada menu Material.
                    </p>
                @else
                    <form method="POST" action="{{ route('bom-items.store', $selectedProduct) }}" class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end" data-material-combobox>
                        @csrf
                        <div>
                            <label for="material_id" class="mb-1.5 block text-sm font-semibold text-slate-800">Material <span class="text-red-600">*</span></label>
                            <input id="material_id" type="hidden" name="material_id" value="{{ old('material_id') }}" data-material-id>
                            <div class="relative">
                                <input id="material-search" type="search" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="material-options" aria-expanded="false"
                                       placeholder="Cari SKU atau nama material..." data-material-search
                                       @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('material_id'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('material_id')])>
                                <div id="material-options" role="listbox" class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg" data-material-options>
                                @foreach ($materials as $material)
                                    <button type="button" role="option" aria-selected="false"
                                            data-material-option data-id="{{ $material->id }}"
                                            data-label="{{ $material->sku }} — {{ $material->name }} ({{ $material->unit }})"
                                            data-search="{{ \Illuminate\Support\Str::lower($material->sku.' '.$material->name.' '.$material->unit) }}"
                                            class="flex w-full items-center justify-between rounded-md px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-blue-50 hover:text-blue-800 focus:bg-blue-50 focus:outline-none">
                                        <span><span class="font-mono text-xs font-semibold">{{ $material->sku }}</span><span class="ml-2">{{ $material->name }}</span></span>
                                        <span class="ml-3 shrink-0 text-xs text-slate-500">{{ $material->unit }}</span>
                                    </button>
                                @endforeach
                                </div>
                            </div>
                            @error('material_id') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="quantity_per_product" class="mb-1.5 block text-sm font-semibold text-slate-800">Kuantitas / unit <span class="text-red-600">*</span></label>
                            <input id="quantity_per_product" name="quantity_per_product" type="number" min="0.01" step="0.01" value="{{ old('quantity_per_product') }}" required
                                   placeholder="Contoh: 2.50"
                                   @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('quantity_per_product'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('quantity_per_product')])>
                            @error('quantity_per_product') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-50" @disabled($materials->isEmpty())>
                            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            Tambah
                        </button>
                    </form>
                @endif

                @error('product')
                    <p role="alert" class="mt-3 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </section>
        @endif

        <section aria-label="Daftar material BOM" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
                <h2 class="text-sm font-semibold text-slate-900">Material penyusun</h2>
                <p class="mt-1 text-xs text-slate-500">Kuantitas menunjukkan jumlah material yang diperlukan untuk satu unit {{ $selectedProduct->name }}.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3.5">SKU Material</th>
                            <th scope="col" class="px-5 py-3.5">Nama Material</th>
                            <th scope="col" class="px-5 py-3.5">Kuantitas / Produk</th>
                            <th scope="col" class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($bomItems as $bomItem)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-semibold text-slate-600">{{ $bomItem->material->sku }}</td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-slate-900">{{ $bomItem->material->name }}</span>
                                    <span class="ml-1.5 text-xs text-slate-500">({{ $bomItem->material->unit }})</span>
                                    @if (! $bomItem->material->is_active)
                                        <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-800">
                                    {{ $bomItem->quantity_per_product }}
                                    <span class="ml-1 text-xs font-normal text-slate-500">{{ $bomItem->material->unit }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('bom-items.edit', $bomItem) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('bom-items.destroy', $bomItem) }}" onsubmit="return confirm('Hapus baris material ini dari BOM?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex min-h-9 items-center rounded-lg border border-red-200 bg-white px-3 text-xs font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-14 text-center">
                                    <p class="text-sm font-semibold text-slate-800">Belum ada material dalam BOM</p>
                                    <p class="mt-1 text-sm text-slate-500">Tambahkan material aktif menggunakan formulir di atas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
