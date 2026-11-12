@extends('layouts.app')

@section('title', 'Stok Material')

@section('content')
    <div class="mb-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Inventory</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Stok Material</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Pantau saldo material, catat penerimaan, dan sesuaikan saldo berdasarkan hasil pemeriksaan fisik.</p>
    </div>

    <div class="mb-6 grid gap-5 xl:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-slate-900">Penerimaan Stok</h2>
                <p class="mt-1 text-xs text-slate-500">Menambahkan kuantitas ke saldo dan mencatat movement penerimaan.</p>
            </div>
            <form method="POST" action="{{ route('inventory.receipts.store') }}" class="space-y-4" data-material-combobox>
                @csrf
                <div>
                    <label for="receipt-material-search" class="mb-1.5 block text-sm font-semibold text-slate-800">Material <span class="text-red-600">*</span></label>
                    <input id="receipt-material-id" type="hidden" name="material_id" value="{{ old('_form') === 'receipt' ? old('material_id') : '' }}" data-material-id>
                    <div class="relative">
                        <input id="receipt-material-search" type="search" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="receipt-material-options" aria-expanded="false"
                               placeholder="Cari SKU atau nama material..." data-material-search
                               @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('material_id') && old('_form') === 'receipt', 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! ($errors->has('material_id') && old('_form') === 'receipt')])>
                        <div id="receipt-material-options" role="listbox" class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg" data-material-options>
                        @foreach ($materialOptions as $material)
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
                    @if (old('_form') === 'receipt')
                        @error('material_id') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                    @endif
                </div>
                <div>
                    <label for="receipt-quantity" class="mb-1.5 block text-sm font-semibold text-slate-800">Jumlah diterima <span class="text-red-600">*</span></label>
                    <input id="receipt-quantity" name="quantity" type="number" min="0.01" step="0.01" value="{{ old('_form') === 'receipt' ? old('quantity') : '' }}" required placeholder="Contoh: 25.00"
                           @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('quantity') && old('_form') === 'receipt', 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! ($errors->has('quantity') && old('_form') === 'receipt')])>
                    @if (old('_form') === 'receipt')
                        @error('quantity') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                    @endif
                </div>
                <div>
                    <label for="receipt-notes" class="mb-1.5 block text-sm font-semibold text-slate-800">Catatan <span class="font-normal text-slate-500">(opsional)</span></label>
                    <textarea id="receipt-notes" name="notes" rows="2" placeholder="Nomor dokumen atau keterangan penerimaan"
                              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">{{ old('_form') === 'receipt' ? old('notes') : '' }}</textarea>
                </div>
                <input type="hidden" name="_form" value="receipt">
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                    Catat Penerimaan
                </button>
            </form>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-slate-900">Penyesuaian Stok</h2>
                <p class="mt-1 text-xs text-slate-500">Masukkan saldo fisik baru. Selisih tercatat sebagai movement adjustment.</p>
            </div>
            <form method="POST" action="{{ route('inventory.adjustments.store') }}" class="space-y-4" data-material-combobox>
                @csrf
                <div>
                    <label for="adjustment-material-search" class="mb-1.5 block text-sm font-semibold text-slate-800">Material <span class="text-red-600">*</span></label>
                    <input id="adjustment-material-id" type="hidden" name="material_id" value="{{ old('_form') === 'adjustment' ? old('material_id') : '' }}" data-material-id>
                    <div class="relative">
                        <input id="adjustment-material-search" type="search" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="adjustment-material-options" aria-expanded="false"
                               placeholder="Cari SKU atau nama material..." data-material-search
                               @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('material_id') && old('_form') === 'adjustment', 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! ($errors->has('material_id') && old('_form') === 'adjustment')])>
                        <div id="adjustment-material-options" role="listbox" class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg" data-material-options>
                        @foreach ($materialOptions as $material)
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
                    @if (old('_form') === 'adjustment')
                        @error('material_id') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                    @endif
                </div>
                <div>
                    <label for="adjustment-on-hand" class="mb-1.5 block text-sm font-semibold text-slate-800">Saldo fisik baru <span class="text-red-600">*</span></label>
                    <input id="adjustment-on-hand" name="on_hand" type="number" min="0" step="0.01" value="{{ old('_form') === 'adjustment' ? old('on_hand') : '' }}" required placeholder="Contoh: 18.50"
                           @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('on_hand'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('on_hand')])>
                    @if (old('_form') === 'adjustment')
                        @error('on_hand') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                    @endif
                </div>
                <div>
                    <label for="adjustment-notes" class="mb-1.5 block text-sm font-semibold text-slate-800">Catatan <span class="font-normal text-slate-500">(opsional)</span></label>
                    <textarea id="adjustment-notes" name="notes" rows="2" placeholder="Alasan atau keterangan penyesuaian"
                              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">{{ old('_form') === 'adjustment' ? old('notes') : '' }}</textarea>
                </div>
                <input type="hidden" name="_form" value="adjustment">
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100">
                    Simpan Penyesuaian
                </button>
            </form>
        </section>
    </div>

    <section aria-label="Saldo material" class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-sm font-semibold text-slate-900">Saldo Material</h2>
            <p class="mt-1 text-xs text-slate-500">Material tanpa transaksi stok ditampilkan dengan saldo nol. Atur stok minimum pada kolom terakhir.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">SKU</th>
                        <th scope="col" class="px-5 py-3.5">Nama Material</th>
                        <th scope="col" class="px-5 py-3.5">Satuan</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Stok Tersedia</th>
                        <th scope="col" class="px-5 py-3.5">Stok Minimum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($materials as $material)
                        @php
                            $onHand = $material->stock?->on_hand ?? '0.00';
                            $reorderLevel = $material->stock?->reorder_level;
                            $isLowStock = $material->stock?->isAtOrBelowReorderLevel() ?? false;
                        @endphp
                        <tr class="hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-semibold text-slate-600">{{ $material->sku }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-900">{{ $material->name }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $material->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <span @class(['font-semibold', 'text-amber-700' => $isLowStock, 'text-slate-900' => ! $isLowStock])>{{ $onHand }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('inventory.reorder-level.update', $material) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input aria-label="Stok minimum {{ $material->name }}" name="reorder_level" type="number" min="0" step="0.01" value="{{ old('_material_id') === (string) $material->id ? old('reorder_level', $reorderLevel) : $reorderLevel }}" placeholder="Tidak diatur"
                                           class="min-h-9 w-36 rounded-lg border border-slate-300 bg-white px-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                    <input type="hidden" name="_material_id" value="{{ $material->id }}">
                                    <button type="submit" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Simpan</button>
                                </form>
                                @if (old('_material_id') === (string) $material->id)
                                    @error('reorder_level') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada data material.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($materials->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $materials->links() }}</div>
        @endif
    </section>

    <section aria-label="Riwayat pergerakan stok" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-sm font-semibold text-slate-900">Riwayat Pergerakan Stok</h2>
            <p class="mt-1 text-xs text-slate-500">Penerimaan dan penyesuaian terbaru untuk semua material.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">Waktu</th>
                        <th scope="col" class="px-5 py-3.5">Material</th>
                        <th scope="col" class="px-5 py-3.5">Jenis</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Kuantitas</th>
                        <th scope="col" class="px-5 py-3.5">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movements as $movement)
                        <tr class="hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">{{ $movement->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-5 py-4">
                                <span class="font-mono text-xs font-semibold text-slate-600">{{ $movement->material->sku }}</span>
                                <span class="ml-1 text-slate-800">{{ $movement->material->name }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @switch($movement->movement_type)
                                    @case('receipt')
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Penerimaan</span>
                                        @break
                                    @case('adjustment')
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">Penyesuaian</span>
                                        @break
                                    @case('production_consumption')
                                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Konsumsi Produksi</span>
                                        @break
                                    @default
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $movement->movement_type }}</span>
                                @endswitch
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-slate-800">
                                @if ($movement->movement_type === 'receipt') +@elseif ($movement->movement_type === 'production_consumption') −@elseif ($movement->movement_type === 'adjustment' && str_starts_with($movement->notes ?? '', 'Penyesuaian naik:')) +@elseif ($movement->movement_type === 'adjustment' && str_starts_with($movement->notes ?? '', 'Penyesuaian turun:')) −@endif{{ $movement->quantity }} {{ $movement->material->unit }}
                            </td>
                            <td class="max-w-sm px-5 py-4 text-xs leading-5 text-slate-600">
                                @if ($movement->movement_type === \App\Models\MaterialStockMovement::TYPE_PRODUCTION_CONSUMPTION && $movement->reference instanceof \App\Models\ProductionRealization)
                                    <span>Konsumsi realisasi produksi</span>
                                    <a href="{{ route('production-realizations.show', $movement->reference) }}" class="font-semibold text-blue-700 underline decoration-blue-200 underline-offset-2 hover:text-blue-900">
                                        {{ $movement->reference->transaction_number }}
                                    </a>
                                @else
                                    {{ $movement->notes ?: '—' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada pergerakan stok yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $movements->links() }}</div>
        @endif
    </section>
@endsection
