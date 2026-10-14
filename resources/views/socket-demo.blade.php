<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simple KRS · Reverb Socket Demo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <main class="mx-auto max-w-3xl px-5 py-12 sm:py-20">
        <a href="/" class="text-sm text-sky-300 hover:text-sky-200">&larr; Simple KRS</a>
        <p class="mt-10 text-xs font-semibold uppercase tracking-[.25em] text-sky-300">Phase 01 · WebSocket</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Reverb connection check</h1>
        <p class="mt-4 max-w-2xl leading-7 text-slate-400">Halaman ini mendengarkan channel publik <code class="text-slate-200">demo</code>. Buka di dua tab, lalu kirim pesan dari salah satu tab untuk melihat event masuk di keduanya.</p>

        <section class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-7">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-medium">Status koneksi</h2>
                <span id="connection-status" class="rounded-full bg-slate-800 px-3 py-1 text-xs text-slate-300">Menghubungkan...</span>
            </div>
            <form id="message-form" class="mt-6 flex flex-col gap-3 sm:flex-row">
                <input id="message-input" name="message" maxlength="200" required value="Halo dari Simple KRS" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm outline-none focus:border-sky-400" placeholder="Tulis pesan...">
                <button id="send-button" type="submit" class="rounded-lg bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-sky-300 disabled:cursor-not-allowed disabled:opacity-50">Kirim event</button>
            </form>
            <p id="send-status" role="status" class="mt-3 min-h-5 text-sm text-slate-400"></p>
        </section>

        <section class="mt-6 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-7">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-medium">Event diterima</h2>
                <button id="clear-events" type="button" class="text-xs text-slate-400 hover:text-white">Bersihkan</button>
            </div>
            <ol id="event-list" class="mt-5 space-y-3">
                <li id="empty-state" class="text-sm text-slate-500">Belum ada event. Kirim pesan untuk memulai.</li>
            </ol>
        </section>
        <p class="mt-6 text-xs leading-5 text-slate-500">Demo ini menggunakan channel publik dan hanya untuk pengembangan lokal. Jangan kirim data pribadi. Otorisasi channel privat masuk fase berikutnya.</p>
    </main>

    <script>
        const statusEl = document.getElementById('connection-status');
        const sendStatus = document.getElementById('send-status');
        const eventList = document.getElementById('event-list');
        const form = document.getElementById('message-form');
        const input = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');

        function setConnectionStatus(connected) {
            statusEl.textContent = connected ? 'Terhubung' : 'Terputus';
            statusEl.className = connected
                ? 'rounded-full bg-emerald-400/10 px-3 py-1 text-xs text-emerald-300'
                : 'rounded-full bg-rose-400/10 px-3 py-1 text-xs text-rose-300';
            sendButton.disabled = !connected;
        }

        function addEvent(payload) {
            document.getElementById('empty-state')?.remove();
            const item = document.createElement('li');
            item.className = 'rounded-lg border border-slate-800 bg-slate-950 p-4';
            const message = document.createElement('p');
            message.className = 'break-words text-sm text-slate-100';
            message.textContent = payload.message;
            const time = document.createElement('p');
            time.className = 'mt-2 text-xs text-slate-500';
            time.textContent = payload.sent_at ? new Date(payload.sent_at).toLocaleTimeString() : 'Waktu tidak tersedia';
            item.append(message, time);
            eventList.prepend(item);
        }

        document.getElementById('clear-events').addEventListener('click', () => {
            eventList.replaceChildren();
            const empty = document.createElement('li');
            empty.id = 'empty-state';
            empty.className = 'text-sm text-slate-500';
            empty.textContent = 'Daftar event dibersihkan.';
            eventList.append(empty);
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            sendButton.disabled = true;
            sendStatus.textContent = 'Mengirim...';
            try {
                const response = await fetch(@json(route('socket-demo.publish')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify({ message: input.value }),
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Pesan gagal dikirim.');
                }
                sendStatus.textContent = 'Event berhasil dipublikasikan. Tunggu event masuk melalui WebSocket.';
            } catch (error) {
                sendStatus.textContent = error.message || 'Gagal mengirim event.';
            } finally {
                sendButton.disabled = !window.Echo || window.Echo.connector.pusher.connection.state !== 'connected';
            }
        });

        if (window.Echo) {
            const connection = window.Echo.connector.pusher.connection;
            connection.bind('connected', () => setConnectionStatus(true));
            connection.bind('disconnected', () => setConnectionStatus(false));
            connection.bind('unavailable', () => setConnectionStatus(false));
            connection.bind('error', () => setConnectionStatus(false));
            setConnectionStatus(connection.state === 'connected');

            window.Echo.channel('demo').listen('.demo.message', (payload) => {
                addEvent(payload);
            });
        } else {
            setConnectionStatus(false);
            statusEl.textContent = 'Konfigurasi Vite belum tersedia';
            sendStatus.textContent = 'Pastikan VITE_REVERB_APP_KEY terisi di .env lalu jalankan npm run dev atau npm run build.';
        }
    </script>
</body>
</html>
