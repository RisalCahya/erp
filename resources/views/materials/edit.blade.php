@extends('layouts.app')

@section('title', 'Edit Material')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('materials.show', $material) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-700">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
            Kembali ke Detail Material
        </a>
        <div class="mt-5">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Master data</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Edit Material</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Perbarui informasi material <span class="font-semibold text-slate-800">{{ $material->name }}</span>.</p>
        </div>

        <form method="POST" action="{{ route('materials.update', $material) }}" class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @method('PUT')
            <div class="space-y-5 p-5 sm:p-7">
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-sm font-semibold text-slate-900">Informasi material</h2>
                    <p class="mt-1 text-xs text-slate-500">Perubahan akan disimpan ke master material ini.</p>
                </div>
                @include('materials._form')
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
                <a href="{{ route('materials.show', $material) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection
