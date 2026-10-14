<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Katalog Kelas · Simple KRS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <main class="mx-auto max-w-6xl px-5 py-10 sm:py-14">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.25em] text-sky-300">Simple KRS · Fase 02</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Katalog kelas</h1>
                <p class="mt-2 text-sm text-slate-400">Halo, {{ auth()->user()->name }}. Kuota dan sisa kursi saat ini berasal dari database utama.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.index') }}" class="text-sky-300 hover:text-sky-200">Dashboard admin</a>
                @endif
                <a href="{{ route('socket-demo') }}" class="text-slate-400 hover:text-white">Demo socket</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800">Keluar</button></form>
            </div>
        </header>

        <section class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-sm text-slate-400">Total kelas</p><p class="mt-2 text-2xl font-semibold">{{ $courses->count() }}</p></div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-sm text-slate-400">Kursi tersedia</p><p class="mt-2 text-2xl font-semibold">{{ $courses->sum('remaining_seats') }}</p></div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-sm text-slate-400">Peran akun</p><p class="mt-2 text-2xl font-semibold capitalize">{{ auth()->user()->role }}</p></div>
        </section>

        <section class="mt-8 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
            <div class="border-b border-slate-800 px-5 py-4"><h2 class="font-medium">Daftar kelas</h2><p class="mt-1 text-sm text-slate-400">Data contoh untuk pengembangan. Pengambilan kelas akan tersedia pada fase berikutnya.</p></div>
            @if ($courses->isEmpty())
                <p class="p-6 text-sm text-slate-400">Belum ada kelas. Jalankan seeder database untuk mengisi data contoh.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-slate-950 text-xs uppercase tracking-wide text-slate-400"><tr><th class="px-5 py-3">Kode</th><th class="px-5 py-3">Mata kuliah</th><th class="px-5 py-3">Dosen</th><th class="px-5 py-3">Jadwal</th><th class="px-5 py-3">SKS</th><th class="px-5 py-3">Sisa / Kuota</th></tr></thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach ($courses as $course)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 font-mono text-sky-300">{{ $course->course_code }}</td>
                                    <td class="px-5 py-4 font-medium">{{ $course->title }}</td>
                                    <td class="px-5 py-4 text-slate-300">{{ $course->lecturer }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-300">{{ $course->day_of_week }}, {{ substr($course->starts_at, 0, 5) }}–{{ substr($course->ends_at, 0, 5) }}</td>
                                    <td class="px-5 py-4">{{ $course->credits }}</td>
                                    <td class="px-5 py-4"><span class="font-semibold">{{ $course->remaining_seats }}</span><span class="text-slate-500"> / {{ $course->capacity }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </main>
</body>
</html>
