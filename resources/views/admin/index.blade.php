<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin · Simple KRS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <main class="mx-auto max-w-5xl px-5 py-10 sm:py-14">
        <a href="{{ route('classes.index') }}" class="text-sm text-sky-300">← Kembali ke katalog</a>
        <p class="mt-8 text-xs font-semibold uppercase tracking-[.25em] text-sky-300">Area admin</p>
        <h1 class="mt-2 text-3xl font-semibold">Ringkasan sistem</h1>
        <p class="mt-2 text-sm text-slate-400">Halaman ini dibatasi untuk akun dengan peran admin.</p>
        <section class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-5"><p class="text-sm text-slate-400">Mahasiswa</p><p class="mt-2 text-3xl font-semibold">{{ $studentCount }}</p></div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-5"><p class="text-sm text-slate-400">Kelas</p><p class="mt-2 text-3xl font-semibold">{{ $courseCount }}</p></div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-5"><p class="text-sm text-slate-400">Enrollment</p><p class="mt-2 text-3xl font-semibold">{{ $enrollmentCount }}</p></div>
        </section>
        <p class="mt-6 rounded-xl border border-slate-800 bg-slate-900 p-5 text-sm leading-6 text-slate-400">Manajemen kelas dan periode belum termasuk fase ini. Data ringkasan akan berkembang bersama fitur enrollment dan monitoring.</p>
    </main>
</body>
</html>
