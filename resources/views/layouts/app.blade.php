<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Production Planning') · {{ config('app.name', 'Production Planning') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="hidden w-64 shrink-0 flex-col bg-slate-950 text-slate-300 lg:flex">
            <a href="{{ route('products.index') }}" class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
                <span class="flex size-10 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold text-white">P</span>
                <span>
                    <span class="block text-sm font-bold tracking-wide text-white">Production Planning</span>
                    <span class="mt-0.5 block text-xs text-slate-400">Material requirement system</span>
                </span>
            </a>

            <nav aria-label="Navigasi utama" class="flex-1 space-y-1 px-3 py-6">
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Menu utama</p>

                <a href="{{ route('dashboard') }}"
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-600 font-semibold text-white shadow-sm shadow-blue-950/30' => request()->routeIs('dashboard'), 'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('dashboard')])>
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg>
                    <span class="flex-1">Dashboard</span>
                </a>

                <a href="{{ route('products.index') }}"
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-600 font-semibold text-white shadow-sm shadow-blue-950/30' => request()->routeIs('products.*'), 'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('products.*')])>
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v9M8 5.3l8 4.5"/></svg>
                    <span>Produk</span>
                </a>

                <a href="{{ route('materials.index') }}"
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-600 font-semibold text-white shadow-sm shadow-blue-950/30' => request()->routeIs('materials.*'), 'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('materials.*')])>
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3 4 7.5v9L12 21l8-4.5v-9L12 3Z"/><path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v9"/></svg>
                    <span>Material</span>
                </a>

                <a href="{{ route('bom.index') }}"
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-600 font-semibold text-white shadow-sm shadow-blue-950/30' => request()->routeIs('bom.*', 'bom-items.*'), 'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('bom.*', 'bom-items.*')])>
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
                    <span class="flex-1">BOM</span>
                </a>

                <a href="{{ route('inventory.index') }}"
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-600 font-semibold text-white shadow-sm shadow-blue-950/30' => request()->routeIs('inventory.*'), 'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('inventory.*')])>
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 7h18v13H3zM3 7l2-4h14l2 4"/><path d="M8 11v5m4-5v5m4-5v5"/></svg>
                    <span>Stok Material</span>
                </a>

                <a href="{{ route('production-plans.index') }}"
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-600 font-semibold text-white shadow-sm shadow-blue-950/30' => request()->routeIs('production-plans.*'), 'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('production-plans.*')])>
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M7 2.5v4M17 2.5v4M3 9h18M8 13h3M8 17h7"/></svg>
                    <span class="flex-1">Rencana Produksi</span>
                </a>

                <a href="{{ route('production-realizations.index') }}"
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-600 font-semibold text-white shadow-sm shadow-blue-950/30' => request()->routeIs('production-realizations.*'), 'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('production-realizations.*')])>
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h3m-3 4h8m-1-5 2 2 4-4"/></svg>
                    <span class="flex-1">Realisasi Produksi</span>
                </a>

                <a href="{{ route('material-requirements.index') }}"
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-600 font-semibold text-white shadow-sm shadow-blue-950/30' => request()->routeIs('material-requirements.*'), 'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('material-requirements.*')])>
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 19V5M4 19h17"/><path d="m7 15 4-4 3 2 6-7"/></svg>
                    <span class="flex-1">Kebutuhan Material</span>
                </a>
            </nav>

            <div class="border-t border-white/10 px-6 py-4 text-xs leading-5 text-slate-500">
                Aplikasi simulasi untuk perencanaan produksi.
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="border-b border-slate-200 bg-white">
                <div class="flex min-h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-blue-700 text-sm font-bold text-white lg:hidden">P</span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Production Planning</p>
                            <p class="hidden text-xs text-slate-500 sm:block">Perencanaan produksi dan material</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="hidden text-sm font-medium text-slate-600 sm:inline">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
                <nav aria-label="Navigasi mobile" class="flex gap-1 overflow-x-auto border-t border-slate-100 px-3 py-2 lg:hidden">
                    <a href="{{ route('products.index') }}"
                       @class(['shrink-0 rounded-lg px-3 py-2 text-sm font-medium', 'bg-blue-700 text-white' => request()->routeIs('products.*'), 'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('products.*')])>
                        Produk
                    </a>
                    <a href="{{ route('materials.index') }}"
                       @class(['shrink-0 rounded-lg px-3 py-2 text-sm font-medium', 'bg-blue-700 text-white' => request()->routeIs('materials.*'), 'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('materials.*')])>
                        Material
                    </a>
                    <a href="{{ route('dashboard') }}"
                       @class(['shrink-0 rounded-lg px-3 py-2 text-sm font-medium', 'bg-blue-700 text-white' => request()->routeIs('dashboard'), 'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('dashboard')])>
                        Dashboard
                    </a>
                    <a href="{{ route('bom.index') }}"
                       @class(['shrink-0 rounded-lg px-3 py-2 text-sm font-medium', 'bg-blue-700 text-white' => request()->routeIs('bom.*', 'bom-items.*'), 'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('bom.*', 'bom-items.*')])>
                        BOM
                    </a>
                    <a href="{{ route('inventory.index') }}"
                       @class(['shrink-0 rounded-lg px-3 py-2 text-sm font-medium', 'bg-blue-700 text-white' => request()->routeIs('inventory.*'), 'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('inventory.*')])>
                        Stok Material
                    </a>
                    <a href="{{ route('production-plans.index') }}"
                       @class(['shrink-0 rounded-lg px-3 py-2 text-sm font-medium', 'bg-blue-700 text-white' => request()->routeIs('production-plans.*'), 'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('production-plans.*')])>
                        Rencana Produksi
                    </a>
                    <a href="{{ route('production-realizations.index') }}"
                       @class(['shrink-0 rounded-lg px-3 py-2 text-sm font-medium', 'bg-blue-700 text-white' => request()->routeIs('production-realizations.*'), 'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('production-realizations.*')])>
                        Realisasi Produksi
                    </a>
                    <a href="{{ route('material-requirements.index') }}"
                       @class(['shrink-0 rounded-lg px-3 py-2 text-sm font-medium', 'bg-blue-700 text-white' => request()->routeIs('material-requirements.*'), 'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('material-requirements.*')])>
                        Kebutuhan Material
                    </a>
                </nav>
            </header>

            <main class="mx-auto w-full max-w-[1440px] px-4 py-6 sm:px-6 sm:py-8 lg:px-10">
                @if (session('success'))
                    <div role="status" class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-900 shadow-sm">
                        <svg aria-hidden="true" class="mt-0.5 size-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5 text-sm text-red-900 shadow-sm">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-900 shadow-sm">
                        <div class="flex items-start gap-3">
                            <svg aria-hidden="true" class="mt-0.5 size-5 shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01"/><path d="M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                            <div>
                                <p class="font-semibold">Periksa kembali data yang dimasukkan.</p>
                                <ul class="mt-2 list-inside list-disc space-y-1 text-red-800">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
