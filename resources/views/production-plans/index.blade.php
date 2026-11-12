@extends('layouts.app')

@section('title', 'Rencana Produksi')

@section('content')
    <div class="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Perencanaan</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Rencana Produksi</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Buat rencana berdasarkan BOM dan tinjau kesiapan material tanpa mengubah saldo stok.</p>
        </div>
        <a href="{{ route('production-plans.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Buat Rencana
        </a>
    </div>

    <section aria-label="Daftar rencana produksi" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-sm font-semibold text-slate-900">Daftar Rencana</h2>
            <p class="mt-1 text-xs text-slate-500">Kebutuhan material disimpan sebagai snapshot saat rencana dibuat.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">Nomor Rencana</th>
                        <th scope="col" class="px-5 py-3.5">Produk</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Jumlah</th>
                        <th scope="col" class="px-5 py-3.5">Tanggal Dibutuhkan</th>
                        <th scope="col" class="px-5 py-3.5">Status</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($productionPlans as $productionPlan)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-semibold text-slate-700">{{ $productionPlan->plan_number }}</td>
                            <td class="px-5 py-4">
                                <span class="font-semibold text-slate-900">{{ $productionPlan->product->name }}</span>
                                <span class="mt-1 block font-mono text-xs text-slate-500">{{ $productionPlan->product->sku }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-slate-800">{{ $productionPlan->planned_quantity }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $productionPlan->due_date->format('d M Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @switch($productionPlan->status)
                                    @case('planned')
                                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Direncanakan</span>
                                        @break
                                    @case('completed')
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selesai</span>
                                        @break
                                    @case('cancelled')
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Dibatalkan</span>
                                        @break
                                    @default
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">Draft</span>
                                @endswitch
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('production-plans.show', $productionPlan) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-800">
                                        Lihat Kebutuhan
                                    </a>
                                    <form method="POST" action="{{ route('production-plans.destroy', $productionPlan) }}" onsubmit="return confirm('Hapus rencana produksi ini beserta snapshot kebutuhan materialnya?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex min-h-9 items-center rounded-lg border border-red-200 bg-white px-3 text-xs font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                    <svg aria-hidden="true" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M7 2.5v4M17 2.5v4M3 9h18M8 13h3M8 17h7"/></svg>
                                </span>
                                <p class="mt-3 text-sm font-semibold text-slate-800">Belum ada rencana produksi</p>
                                <p class="mt-1 text-sm text-slate-500">Buat rencana baru untuk menghitung kebutuhan material dari BOM.</p>
                                <a href="{{ route('production-plans.create') }}" class="mt-4 inline-flex min-h-9 items-center rounded-lg bg-blue-700 px-3.5 text-xs font-semibold text-white hover:bg-blue-800">Buat Rencana</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($productionPlans->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $productionPlans->links() }}</div>
        @endif
    </section>
@endsection
