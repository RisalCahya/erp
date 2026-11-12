@extends('layouts.app')

@section('title', 'Realisasi Produksi')

@section('content')
    <div class="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Produksi</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Realisasi Produksi</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Simpan draft tanpa mengubah stok, lalu posting realisasi untuk mencatat konsumsi material dan hasil barang jadi.</p>
        </div>
        <a href="{{ route('production-realizations.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Buat Realisasi
        </a>
    </div>

    <section aria-label="Daftar realisasi produksi" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-sm font-semibold text-slate-900">Daftar Transaksi</h2>
            <p class="mt-1 text-xs text-slate-500">Transaksi yang sudah completed tidak dapat diposting ulang.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">Nomor Transaksi</th>
                        <th scope="col" class="px-5 py-3.5">Tanggal</th>
                        <th scope="col" class="px-5 py-3.5">Produk Jadi</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Qty Finished Good</th>
                        <th scope="col" class="px-5 py-3.5">Status</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($realizations as $realization)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-semibold text-slate-700">{{ $realization->transaction_number }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $realization->transaction_date->format('d M Y') }}</td>
                            <td class="px-5 py-4">
                                <span class="font-semibold text-slate-900">{{ $realization->product->name }}</span>
                                <span class="mt-1 block font-mono text-xs text-slate-500">{{ $realization->product->sku }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-slate-800">{{ $realization->finished_good_quantity }} {{ $realization->output_unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($realization->status === \App\Models\ProductionRealization::STATUS_COMPLETED)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Completed</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">Draft</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <a href="{{ route('production-realizations.show', $realization) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-800">Lihat Transaksi</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-slate-800">Belum ada transaksi realisasi</p>
                                <p class="mt-1 text-sm text-slate-500">Buat draft untuk menghitung kebutuhan material sebelum stok berubah.</p>
                                <a href="{{ route('production-realizations.create') }}" class="mt-4 inline-flex min-h-9 items-center rounded-lg bg-blue-700 px-3.5 text-xs font-semibold text-white hover:bg-blue-800">Buat Realisasi</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($realizations->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $realizations->links() }}</div>
        @endif
    </section>
@endsection
