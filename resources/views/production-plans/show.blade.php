@extends('layouts.app')

@section('title', 'Detail Rencana Produksi')

@section('content')
    <div class="mb-5">
        <a href="{{ route('production-plans.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-700">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
            Kembali ke Rencana Produksi
        </a>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-5 border-b border-slate-200 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-7">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Detail Rencana</p>
                <h1 class="mt-2 font-mono text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">{{ $productionPlan->plan_number }}</h1>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $productionPlan->product->name }} <span class="ml-1 font-mono text-xs font-normal text-slate-500">{{ $productionPlan->product->sku }}</span></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex w-fit rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">Direncanakan</span>
                <form method="POST" action="{{ route('production-plans.destroy', $productionPlan) }}" onsubmit="return confirm('Hapus rencana produksi ini beserta snapshot kebutuhan materialnya?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex min-h-9 items-center rounded-lg border border-red-200 bg-white px-3 text-xs font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50">
                        Hapus Rencana
                    </button>
                </form>
            </div>
        </div>
        <dl class="grid gap-4 p-5 sm:grid-cols-3 sm:p-7">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Produksi</dt>
                <dd class="mt-1 text-lg font-bold text-slate-900">{{ $productionPlan->planned_quantity }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Dibutuhkan</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $productionPlan->due_date->format('d M Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kesiapan Material Rencana Ini</dt>
                <dd class="mt-1">
                    @if ($shortageCount === 0)
                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Material cukup</span>
                    @else
                        <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">{{ $shortageCount }} material kurang</span>
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    <section aria-label="Kebutuhan material rencana" class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-sm font-semibold text-slate-900">Kebutuhan Material Rencana Ini</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Kebutuhan adalah snapshot BOM saat rencana dibuat. Stok tersedia dibaca saat ini dan tidak dikurangi.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">Material</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Kebutuhan</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Stok Tersedia</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Kekurangan</th>
                        <th scope="col" class="px-5 py-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($planRequirements as $row)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <span class="font-semibold text-slate-900">{{ $row['material']->name }}</span>
                                <span class="mt-1 block font-mono text-xs text-slate-500">{{ $row['material']->sku }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-slate-800">{{ $row['required_quantity'] }} {{ $row['material']->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-700">{{ $row['available_quantity'] }} {{ $row['material']->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold {{ $row['status'] === 'kurang' ? 'text-red-700' : 'text-slate-500' }}">{{ $row['shortage_quantity'] }} {{ $row['material']->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($row['status'] === 'cukup')
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Cukup</span>
                                @else
                                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Kurang</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Snapshot belum memiliki detail kebutuhan material.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-5">
        <a href="{{ route('material-requirements.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">
            Lihat ringkasan kebutuhan material seluruh rencana planned
            <span aria-hidden="true">→</span>
        </a>
    </div>
@endsection
