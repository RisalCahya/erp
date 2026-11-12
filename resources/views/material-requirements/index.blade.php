@extends('layouts.app')

@section('title', 'Kebutuhan Material')

@section('content')
    <div class="mb-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Perencanaan</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Kebutuhan Material</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Ringkasan kebutuhan gabungan dari snapshot BOM seluruh rencana berstatus planned. Kebutuhan dijumlahkan per material sebelum dibandingkan satu kali dengan saldo stok terkini.</p>
    </div>

    <section class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <form method="GET" action="{{ route('material-requirements.index') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" data-product-combobox data-combobox-optional>
            <div class="sm:col-span-2 xl:col-span-1">
                <label for="requirement-product-search" class="mb-1.5 block text-sm font-semibold text-slate-800">Produk</label>
                <input type="hidden" name="product_id" value="{{ $selectedProduct?->id }}" data-product-id>
                <div class="relative">
                    <input id="requirement-product-search" type="search" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="requirement-product-options" aria-expanded="false"
                           value="{{ $selectedProduct ? $selectedProduct->sku.' — '.$selectedProduct->name.(!$selectedProduct->is_active ? ' (Nonaktif)' : '') : '' }}"
                           placeholder="Semua produk" data-product-search
                           class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">
                    <div id="requirement-product-options" role="listbox" class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg" data-product-options>
                        @foreach ($products as $product)
                            <button type="button" role="option" aria-selected="{{ $selectedProduct?->id === $product->id ? 'true' : 'false' }}"
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
                <p class="mt-1.5 text-xs text-slate-500">Cari berdasarkan SKU atau nama produk.</p>
            </div>
            <div>
                <label for="due_date_from" class="mb-1.5 block text-sm font-semibold text-slate-800">Tanggal dibutuhkan dari</label>
                <input id="due_date_from" name="due_date_from" type="date" value="{{ $filters['due_date_from'] ?? '' }}"
                       class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">
            </div>
            <div>
                <label for="due_date_to" class="mb-1.5 block text-sm font-semibold text-slate-800">Tanggal dibutuhkan sampai</label>
                <input id="due_date_to" name="due_date_to" type="date" value="{{ $filters['due_date_to'] ?? '' }}"
                       class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                    Terapkan Filter
                </button>
                <a href="{{ route('material-requirements.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section aria-label="Ringkasan kebutuhan material" class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Material dibutuhkan</p>
            <p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($materialCount) }}</p>
            <p class="mt-1 text-xs text-slate-500">Jenis material unik dalam hasil filter</p>
        </article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">Stok cukup</p>
            <p class="mt-2 text-2xl font-bold text-emerald-900">{{ number_format($sufficientCount) }}</p>
            <p class="mt-1 text-xs text-emerald-800">Material dengan saldo mencukupi</p>
        </article>
        <article class="rounded-xl border border-red-200 bg-red-50/70 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-800">Mengalami kekurangan</p>
            <p class="mt-2 text-2xl font-bold text-red-900">{{ number_format($shortageCount) }}</p>
            <p class="mt-1 text-xs text-red-800">Material yang perlu dipenuhi</p>
        </article>
        <article class="rounded-xl border border-blue-200 bg-blue-50/70 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-800">Rencana diperhitungkan</p>
            <p class="mt-2 text-2xl font-bold text-blue-950">{{ number_format($planCount) }}</p>
            <p class="mt-1 text-xs text-blue-800">Hanya rencana berstatus planned</p>
        </article>
    </section>

    <section aria-label="Ringkasan kebutuhan material per material" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-sm font-semibold text-slate-900">Kebutuhan Gabungan per Material</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Data kebutuhan memakai snapshot masing-masing rencana. Membuka halaman ini tidak mengurangi stok dan realisasi completed tidak dihitung sebagai kebutuhan mendatang.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1150px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">SKU Material</th>
                        <th scope="col" class="px-5 py-3.5">Nama Material</th>
                        <th scope="col" class="px-5 py-3.5">Satuan</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Total Kebutuhan</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Stok Tersedia</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Kekurangan</th>
                        <th scope="col" class="px-5 py-3.5">Status Material</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Jumlah Rencana</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($materialRequirements as $row)
                        <tr class="align-top transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-semibold text-slate-700">{{ $row['material']->sku }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-900">{{ $row['material']->name }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $row['material']->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-900">{{ $row['required_quantity'] }} {{ $row['material']->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-700">{{ $row['available_quantity'] }} {{ $row['material']->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold {{ $row['status'] === 'kurang' ? 'text-red-700' : 'text-slate-500' }}">{{ $row['shortage_quantity'] }} {{ $row['material']->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($row['status'] === 'cukup')
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Cukup</span>
                                @else
                                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Kurang</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="font-semibold text-slate-800">{{ $row['plan_count'] }}</span>
                                <details class="mt-1 text-left">
                                    <summary class="cursor-pointer whitespace-nowrap text-xs font-medium text-blue-700 hover:text-blue-800">Lihat rencana</summary>
                                    <ul class="mt-2 min-w-48 space-y-1.5">
                                        @foreach ($row['plans'] as $plan)
                                            <li>
                                                <a href="{{ route('production-plans.show', $plan) }}" class="font-mono text-xs font-semibold text-blue-700 hover:underline">{{ $plan->plan_number }}</a>
                                                <span class="block text-xs text-slate-500">{{ $plan->product->name }} · {{ $plan->due_date->format('d M Y') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-slate-800">Tidak ada kebutuhan material untuk filter ini</p>
                                <p class="mt-1 text-sm text-slate-500">Buat atau pilih rencana produksi berstatus planned yang sesuai filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($materialRequirements->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $materialRequirements->links() }}</div>
        @endif
    </section>
@endsection
