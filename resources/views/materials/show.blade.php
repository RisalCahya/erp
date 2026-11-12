@extends('layouts.app')

@section('title', 'Detail Material')

@section('content')
    <div class="mx-auto max-w-4xl">
        <a href="{{ route('materials.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-700">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
            Kembali ke Material
        </a>
        <section class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-5 border-b border-slate-200 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-7">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Detail material</p>
                    <p class="mt-3 font-mono text-sm font-semibold text-slate-500">{{ $material->sku }}</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $material->name }}</h1>
                    <span @class(['mt-3 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200' => $material->is_active, 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200' => ! $material->is_active])>
                        <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $material->is_active, 'bg-slate-400' => ! $material->is_active])></span>
                        {{ $material->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <a href="{{ route('materials.edit', $material) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-800">
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m14 5 5 5M4 20l4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z"/></svg>
                    Edit Material
                </a>
            </div>
            <dl class="divide-y divide-slate-100 px-5 sm:px-7">
                <div class="grid gap-1 py-4 sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">Satuan</dt>
                    <dd class="text-sm text-slate-800 sm:col-span-2">{{ $material->unit }}</dd>
                </div>
                <div class="grid gap-1 py-4 sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">Dibuat</dt>
                    <dd class="text-sm text-slate-800 sm:col-span-2">{{ $material->created_at->format('d M Y, H:i') }}</dd>
                </div>
                <div class="grid gap-1 py-4 sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">Terakhir diperbarui</dt>
                    <dd class="text-sm text-slate-800 sm:col-span-2">{{ $material->updated_at->format('d M Y, H:i') }}</dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
