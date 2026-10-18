<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Simple KRS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-12">
        <a href="/" class="text-sm text-sky-300">Simple KRS</a>
        <h1 class="mt-6 text-3xl font-semibold">Masuk</h1>
        <p class="mt-2 text-sm text-slate-400">Masuk untuk melihat katalog kelas dan kuota.</p>
        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-rose-800 bg-rose-950/40 p-3 text-sm text-rose-200">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm">Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-sm">
            </label>
            <label class="block text-sm">Kata sandi
                <input type="password" name="password" required autocomplete="current-password" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-sm">
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-700 bg-slate-900"> Ingat saya
            </label>
            <button class="w-full rounded-lg bg-sky-400 px-4 py-3 text-sm font-semibold text-slate-950">Masuk</button>
        </form>
        <p class="mt-5 text-sm text-slate-400">Belum punya akun? <a href="{{ route('register') }}" class="text-sky-300">Daftar sebagai mahasiswa</a></p>
        <a href="{{ route('socket-demo') }}" class="mt-4 text-sm text-slate-500 hover:text-slate-300">Buka demo WebSocket Fase 1</a>
    </main>
</body>
</html>
