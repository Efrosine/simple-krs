<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar · Simple KRS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-12">
        <a href="/" class="text-sm text-sky-300">Simple KRS</a>
        <h1 class="mt-6 text-3xl font-semibold">Buat akun mahasiswa</h1>
        <p class="mt-2 text-sm text-slate-400">Akun yang dibuat dari halaman ini selalu memiliki peran mahasiswa.</p>
        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-rose-800 bg-rose-950/40 p-3 text-sm text-rose-200">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm">Nama
                <input name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-sm">
            </label>
            <label class="block text-sm">Email
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-sm">
            </label>
            <label class="block text-sm">Kata sandi
                <input type="password" name="password" required minlength="8" autocomplete="new-password" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-sm">
            </label>
            <label class="block text-sm">Ulangi kata sandi
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-3 text-sm">
            </label>
            <button class="w-full rounded-lg bg-sky-400 px-4 py-3 text-sm font-semibold text-slate-950">Daftar</button>
        </form>
        <p class="mt-5 text-sm text-slate-400">Sudah punya akun? <a href="{{ route('login') }}" class="text-sky-300">Masuk</a></p>
    </main>
</body>
</html>
