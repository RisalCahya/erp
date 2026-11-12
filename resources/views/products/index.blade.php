@extends('layouts.app')

@section('title', 'Produk')

@section('content')
    <div class="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">Master data</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Produk</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Kelola daftar produk yang akan digunakan dalam perencanaan produksi.</p>
        </div>
        <a href="{{ route('products.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Produk
        </a>
    </div>

    <section aria-label="Daftar produk" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Daftar produk</h2>
                <p class="mt-1 text-xs text-slate-500">Cari berdasarkan SKU atau nama produk.</p>
            </div>
            <form method="GET" action="{{ route('products.index') }}" class="flex w-full gap-2 sm:max-w-md">
                <label for="search" class="sr-only">Cari produk</label>
                <div class="relative min-w-0 flex-1">
                    <svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Cari produk..."
                           class="min-h-10 w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">
                </div>
                <button type="submit" class="min-h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100">Cari</button>
                @if ($search !== '')
                    <a href="{{ route('products.index') }}" class="inline-flex min-h-10 items-center rounded-lg px-2 text-sm font-medium text-slate-500 hover:text-slate-900">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">SKU</th>
                        <th scope="col" class="px-5 py-3.5">Nama Produk</th>
                        <th scope="col" class="px-5 py-3.5">Satuan Output</th>
                        <th scope="col" class="px-5 py-3.5">Status</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-semibold text-slate-600">{{ $product->sku }}</td>
                            <td class="px-5 py-4">
                                <a href="{{ route('products.show', $product) }}" class="font-semibold text-slate-900 hover:text-blue-700">{{ $product->name }}</a>
                                @if ($product->description)
                                    <p class="mt-1 max-w-md truncate text-xs text-slate-500">{{ $product->description }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $product->output_unit ?: 'Belum diatur' }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span @class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200' => $product->is_active, 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200' => ! $product->is_active])>
                                    <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $product->is_active, 'bg-slate-400' => ! $product->is_active])></span>
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('products.edit', $product) }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                        <svg aria-hidden="true" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m14 5 5 5M4 20l4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z"/></svg>
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Hapus produk {{ $product->sku }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 text-xs font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100">
                                            <svg aria-hidden="true" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M10 11v6m4-6v6M5 7l1 14h12l1-14M9 7V4h6v3"/></svg>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                    <svg aria-hidden="true" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v9"/></svg>
                                </span>
                                <p class="mt-3 text-sm font-semibold text-slate-800">{{ $search !== '' ? 'Produk tidak ditemukan' : 'Belum ada produk' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $search !== '' ? 'Coba kata kunci lain.' : 'Tambahkan data produk untuk mulai mengelola master produk.' }}</p>
                                @if ($search === '')
                                    <a href="{{ route('products.create') }}" class="mt-4 inline-flex min-h-9 items-center rounded-lg bg-blue-700 px-3.5 text-xs font-semibold text-white hover:bg-blue-800">Tambah Produk</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
