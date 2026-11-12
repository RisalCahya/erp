@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Ringkasan operasional</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Dashboard</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Pantau kesiapan material, rencana produksi yang berjalan, dan aktivitas realisasi terbaru.</p>
    </div>

    <section aria-label="Ringkasan utama" class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Produk aktif</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($activeProductCount) }}</p>
                </div>
                <span class="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700" aria-hidden="true">P</span>
            </div>
            <a href="{{ route('products.index') }}" class="mt-4 inline-flex text-xs font-semibold text-blue-700 hover:text-blue-800">Lihat produk <span class="ml-1" aria-hidden="true">→</span></a>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Material aktif</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($activeMaterialCount) }}</p>
                </div>
                <span class="flex size-10 items-center justify-center rounded-lg bg-violet-50 text-violet-700" aria-hidden="true">M</span>
            </div>
            <a href="{{ route('materials.index') }}" class="mt-4 inline-flex text-xs font-semibold text-blue-700 hover:text-blue-800">Lihat material <span class="ml-1" aria-hidden="true">→</span></a>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rencana aktif</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($plannedPlanCount) }}</p>
                </div>
                <span class="flex size-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700" aria-hidden="true">R</span>
            </div>
            <a href="{{ route('production-plans.index') }}" class="mt-4 inline-flex text-xs font-semibold text-blue-700 hover:text-blue-800">Lihat rencana <span class="ml-1" aria-hidden="true">→</span></a>
        </article>
        <article class="rounded-xl border {{ $shortageMaterialCount > 0 ? 'border-red-200 bg-red-50/70' : 'border-emerald-200 bg-emerald-50/70' }} p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $shortageMaterialCount > 0 ? 'text-red-800' : 'text-emerald-800' }}">Material kurang</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight {{ $shortageMaterialCount > 0 ? 'text-red-950' : 'text-emerald-950' }}">{{ number_format($shortageMaterialCount) }}</p>
                </div>
                <span class="flex size-10 items-center justify-center rounded-lg {{ $shortageMaterialCount > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}" aria-hidden="true">!</span>
            </div>
            <a href="{{ route('material-requirements.index') }}" class="mt-4 inline-flex text-xs font-semibold {{ $shortageMaterialCount > 0 ? 'text-red-800' : 'text-emerald-800' }}">Tinjau kebutuhan <span class="ml-1" aria-hidden="true">→</span></a>
        </article>
    </section>

    <div class="grid gap-6 2xl:grid-cols-5">
        <section aria-label="Material yang mengalami kekurangan" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm 2xl:col-span-3">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Material yang Perlu Dipenuhi</h2>
                    <p class="mt-1 text-xs text-slate-500">Kebutuhan gabungan dari snapshot seluruh rencana planned, dibandingkan dengan stok satu kali.</p>
                </div>
                <a href="{{ route('material-requirements.index') }}" class="text-xs font-semibold text-blue-700 hover:text-blue-800">Lihat semua kebutuhan <span aria-hidden="true">→</span></a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">SKU Material</th>
                            <th scope="col" class="px-4 py-3">Nama Material</th>
                            <th scope="col" class="px-4 py-3">Satuan</th>
                            <th scope="col" class="px-4 py-3 text-right">Kebutuhan</th>
                            <th scope="col" class="px-4 py-3 text-right">Stok</th>
                            <th scope="col" class="px-4 py-3 text-right">Kurang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($shortageMaterials as $row)
                            <tr class="hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-4 py-3.5 font-mono text-xs font-semibold text-slate-700">{{ $row['material']->sku }}</td>
                                <td class="px-4 py-3.5 font-medium text-slate-900">{{ $row['material']->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-slate-600">{{ $row['material']->unit }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right font-medium text-slate-800">{{ $row['required_quantity'] }} {{ $row['material']->unit }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right text-slate-700">{{ $row['available_quantity'] }} {{ $row['material']->unit }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right font-semibold text-red-700">{{ $row['shortage_quantity'] }} {{ $row['material']->unit }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <span class="mx-auto flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700" aria-hidden="true">✓</span>
                                    <p class="mt-3 text-sm font-semibold text-slate-900">Tidak ada material yang kurang</p>
                                    <p class="mt-1 text-sm text-slate-500">Stok material mencukupi seluruh rencana produksi aktif saat ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($shortageMaterialCount > $shortageMaterials->count())
                <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Menampilkan {{ $shortageMaterials->count() }} dari {{ $shortageMaterialCount }} material yang kurang.</p>
            @endif
        </section>

        <section aria-label="Rencana produksi mendatang" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm 2xl:col-span-2">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Rencana Produksi Aktif</h2>
                    <p class="mt-1 text-xs text-slate-500">Diurutkan dari tanggal dibutuhkan terdekat.</p>
                </div>
                <a href="{{ route('production-plans.index') }}" class="text-xs font-semibold text-blue-700 hover:text-blue-800">Semua rencana <span aria-hidden="true">→</span></a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($upcomingPlans as $plan)
                    <a href="{{ route('production-plans.show', $plan) }}" class="block px-4 py-4 transition hover:bg-slate-50 sm:px-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-mono text-xs font-semibold text-blue-700">{{ $plan->plan_number }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $plan->product->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $plan->planned_quantity }} · Dibutuhkan {{ $plan->due_date->format('d M Y') }}</p>
                            </div>
                            @if (($planShortageCounts[$plan->id] ?? 0) > 0)
                                <span class="shrink-0 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">{{ $planShortageCounts[$plan->id] }} material kurang</span>
                            @else
                                <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Material cukup</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="text-sm font-semibold text-slate-900">Belum ada rencana aktif</p>
                        <p class="mt-1 text-sm text-slate-500">Rencana berstatus draft, dibatalkan, atau selesai tidak ditampilkan.</p>
                        <a href="{{ route('production-plans.create') }}" class="mt-4 inline-flex min-h-9 items-center rounded-lg bg-blue-700 px-3.5 text-xs font-semibold text-white hover:bg-blue-800">Buat Rencana</a>
                    </div>
                @endforelse
            </div>
        </section>

        <section aria-label="Aktivitas realisasi produksi terbaru" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm 2xl:col-span-5">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Aktivitas Realisasi Produksi</h2>
                    <p class="mt-1 text-xs text-slate-500">Transaksi terbaru berdasarkan tanggal transaksi.</p>
                </div>
                <a href="{{ route('production-realizations.index') }}" class="text-xs font-semibold text-blue-700 hover:text-blue-800">Semua realisasi <span aria-hidden="true">→</span></a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[750px] divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Nomor Transaksi</th>
                            <th scope="col" class="px-5 py-3">Tanggal</th>
                            <th scope="col" class="px-5 py-3">Produk Jadi</th>
                            <th scope="col" class="px-5 py-3 text-right">Qty Finished Good</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentRealizations as $realization)
                            <tr class="hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <a href="{{ route('production-realizations.show', $realization) }}" class="font-mono text-xs font-semibold text-blue-700 hover:underline">{{ $realization->transaction_number }}</a>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">{{ $realization->transaction_date->format('d M Y') }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="font-medium text-slate-900">{{ $realization->product->name }}</span>
                                    <span class="mt-0.5 block font-mono text-xs text-slate-500">{{ $realization->product->sku }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right font-medium text-slate-800">{{ $realization->finished_good_quantity }} {{ $realization->output_unit }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    @if ($realization->status === \App\Models\ProductionRealization::STATUS_COMPLETED)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selesai</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">Draft</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada transaksi realisasi produksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
