<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk · Production Planning</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/5">
            <header class="bg-slate-950 px-7 py-6 text-white">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold">P</span>
                    <div>
                        <p class="text-sm font-bold tracking-wide">Production Planning</p>
                        <p class="mt-0.5 text-xs text-slate-400">Material requirement system</p>
                    </div>
                </div>
            </header>
            <div class="p-7">
                <h1 class="text-xl font-bold tracking-tight text-slate-950">Masuk ke aplikasi</h1>
                <p class="mt-1 text-sm leading-6 text-slate-600">Gunakan akun yang diberikan oleh administrator aplikasi.</p>

                @if ($errors->any())
                    <div role="alert" class="mt-5 rounded-lg border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-800">
                        <p class="font-semibold">Tidak dapat masuk</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-800">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                               class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-800">Kata Sandi</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input name="remember" type="checkbox" value="1" class="size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                        Ingat saya
                    </label>
                    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                        Masuk
                    </button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
