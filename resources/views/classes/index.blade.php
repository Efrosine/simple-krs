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
                <p class="text-xs font-semibold uppercase tracking-[.25em] text-sky-300">Simple KRS · Fase 03</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Katalog kelas</h1>
                <p class="mt-2 text-sm text-slate-400">Halo, {{ auth()->user()->name }}. Kuota diperbarui melalui event WebSocket.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.index') }}" class="text-sky-300 hover:text-sky-200">Dashboard admin</a>
                @endif
                <a href="{{ route('socket-demo') }}" class="text-slate-400 hover:text-white">Demo socket</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800">Keluar</button></form>
            </div>
        </header>

        @if (session('status'))
            <div class="mt-6 rounded-lg border border-emerald-800 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</div>
        @endif
        @if ($errors->has('enrollment'))
            <div class="mt-6 rounded-lg border border-rose-800 bg-rose-950/40 px-4 py-3 text-sm text-rose-200">{{ $errors->first('enrollment') }}</div>
        @endif

        <section class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-sm text-slate-400">Total kelas</p><p class="mt-2 text-2xl font-semibold">{{ $courses->count() }}</p></div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-sm text-slate-400">Kursi tersedia</p><p id="total-remaining-seats" class="mt-2 text-2xl font-semibold">{{ $courses->sum('remaining_seats') }}</p></div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-sm text-slate-400">Peran akun</p><p class="mt-2 text-2xl font-semibold capitalize">{{ auth()->user()->role }}</p></div>
        </section>

        <section class="mt-8 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
            <div class="border-b border-slate-800 px-5 py-4"><h2 class="font-medium">Daftar kelas</h2><p class="mt-1 text-sm text-slate-400">Pengambilan kelas dikunci di database agar kuota tidak terlampaui.</p></div>
            @if ($courses->isEmpty())
                <p class="p-6 text-sm text-slate-400">Belum ada kelas. Jalankan seeder database untuk mengisi data contoh.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="bg-slate-950 text-xs uppercase tracking-wide text-slate-400"><tr><th class="px-5 py-3">Kode</th><th class="px-5 py-3">Mata kuliah</th><th class="px-5 py-3">Dosen</th><th class="px-5 py-3">Jadwal</th><th class="px-5 py-3">SKS</th><th class="px-5 py-3">Sisa / Kuota</th><th class="px-5 py-3">Aksi</th></tr></thead>
                        <tbody id="course-rows" class="divide-y divide-slate-800">
                            @foreach ($courses as $course)
                                <tr data-course-id="{{ $course->id }}" data-capacity="{{ $course->capacity }}">
                                    <td class="whitespace-nowrap px-5 py-4 font-mono text-sky-300">{{ $course->course_code }}</td>
                                    <td class="px-5 py-4 font-medium">{{ $course->title }}</td>
                                    <td class="px-5 py-4 text-slate-300">{{ $course->lecturer }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-300">{{ $course->day_of_week }}, {{ substr($course->starts_at, 0, 5) }}–{{ substr($course->ends_at, 0, 5) }}</td>
                                    <td class="px-5 py-4">{{ $course->credits }}</td>
                                    <td class="px-5 py-4"><span data-enrolled-count class="font-semibold">{{ $course->enrollments_count }}</span><span class="text-slate-500"> / {{ $course->capacity }} (tersisa <span data-remaining-seats>{{ $course->remaining_seats }}</span>)</span></td>
                                    <td class="px-5 py-4">
                                        @if ($course->enrollments->isNotEmpty())
                                            <form method="POST" action="{{ route('classes.drop', $course) }}">@csrf @method('DELETE')<button class="rounded-lg border border-rose-800 px-3 py-2 text-rose-200 hover:bg-rose-950">Lepas</button></form>
                                        @elseif ($course->remaining_seats > 0 && auth()->user()->role === 'student')
                                            <form method="POST" action="{{ route('classes.enroll', $course) }}">@csrf<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><button class="rounded-lg bg-sky-400 px-3 py-2 font-semibold text-slate-950 hover:bg-sky-300">Ambil kelas</button></form>
                                        @elseif ($course->remaining_seats < 1)
                                            <span class="text-xs text-rose-300">Penuh</span>
                                        @else
                                            <span class="text-xs text-slate-500">Khusus mahasiswa</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </main>

    <script type="module">
        if (window.Echo) {
            window.Echo.channel('course-capacity').listen('.enrollment.changed', (payload) => {
                const row = document.querySelector(`[data-course-id="${payload.course_id}"]`);
                if (!row) return;

                row.querySelector('[data-enrolled-count]').textContent = payload.enrolled_count;
                row.querySelector('[data-remaining-seats]').textContent = payload.remaining_seats;
                const total = document.getElementById('total-remaining-seats');
                if (total) {
                    let sum = 0;
                    document.querySelectorAll('[data-remaining-seats]').forEach((seat) => sum += Number(seat.textContent));
                    total.textContent = sum;
                }
                if (payload.remaining_seats < 1) {
                    row.querySelectorAll('form[action*="/enroll"] button').forEach((button) => {
                        if (button.textContent.trim() === 'Ambil kelas') button.disabled = true;
                    });
                }
            });
        }
    </script>
</body>
</html>
