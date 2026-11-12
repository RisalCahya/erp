@extends('layouts.app')

@section('title', 'Detail Realisasi Produksi')

@section('content')
    <div class="mb-5">
        <a href="{{ route('production-realizations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-700">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
            Kembali ke Realisasi Produksi
        </a>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-5 border-b border-slate-200 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-7">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Realisasi Barang Jadi</p>
                <h1 class="mt-2 font-mono text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">{{ $productionRealization->transaction_number }}</h1>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $productionRealization->product->name }} <span class="ml-1 font-mono text-xs font-normal text-slate-500">{{ $productionRealization->product->sku }}</span></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($productionRealization->status === \App\Models\ProductionRealization::STATUS_COMPLETED)
                    <span class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">Completed</span>
                @else
                    <span class="inline-flex w-fit rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800">Draft — stok belum berubah</span>
                @endif
                @if ($productionRealization->status === \App\Models\ProductionRealization::STATUS_DRAFT)
                    <form method="POST" action="{{ route('production-realizations.post', $productionRealization) }}" onsubmit="return confirm('Posting realisasi ini akan mengurangi stok material dan menambah stok barang jadi. Lanjutkan?')">
                        @csrf
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Realisasikan Produksi</button>
                    </form>
                @endif
            </div>
        </div>
        <dl class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4 sm:p-7">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Transaksi</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $productionRealization->transaction_date->format('d M Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Qty Finished Good</dt>
                <dd class="mt-1 text-sm font-bold text-slate-900">{{ $productionRealization->finished_good_quantity }} {{ $productionRealization->output_unit }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stok Barang Jadi</dt>
                <dd class="mt-1 text-sm font-bold text-slate-900">{{ $productionRealization->product->finishedGoodStock?->on_hand ?? '0.00' }} {{ $productionRealization->product->finishedGoodStock?->unit ?? $productionRealization->output_unit }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Catatan</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $productionRealization->notes ?: '—' }}</dd>
            </div>
        </dl>
        @if ($productionRealization->status === \App\Models\ProductionRealization::STATUS_DRAFT)
            <p class="border-t border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900">Formula tersimpan sebagai acuan draft. Saat diposting, kebutuhan akan dihitung ulang dari BOM terbaru dan stok dikunci untuk validasi.</p>
        @endif
    </section>

    <section aria-label="Formula kebutuhan material" class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-sm font-semibold text-slate-900">Formula dari BOM</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Kuantitas menunjukkan BOM per satu unit output dan total pemakaian untuk Qty Finished Good.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">SKU Material</th>
                        <th class="px-5 py-3.5">Nama Material</th>
                        <th class="px-5 py-3.5">Satuan Material</th>
                        <th class="px-5 py-3.5 text-right">Qty BOM / Output</th>
                        <th class="px-5 py-3.5 text-right">Qty Finished Good</th>
                        <th class="px-5 py-3.5 text-right">Qty. Pakai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($productionRealization->materials as $line)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-semibold text-slate-700">{{ $line->material->sku }}</td>
                            <td class="px-5 py-4 font-medium text-slate-900">{{ $line->material->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $line->material->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-700">{{ $line->quantity_per_output }} {{ $line->material->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-700">{{ $productionRealization->finished_good_quantity }} {{ $productionRealization->output_unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-900">{{ $line->required_quantity }} {{ $line->material->unit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada snapshot formula. Pastikan produk memiliki BOM yang valid.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
