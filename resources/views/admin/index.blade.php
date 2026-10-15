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
        <section class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">Monitoring cluster</h2>
                    <p class="mt-1 text-sm text-slate-400">Pemeriksaan otomatis setiap 5 detik. Status yang ditampilkan adalah hasil probe saat ini.</p>
                </div>
                <p id="cluster-checked-at" class="text-xs text-slate-500">Memeriksa cluster…</p>
            </div>

            <h3 class="mt-6 text-sm font-semibold text-slate-300">Node aplikasi</h3>
            <div id="cluster-nodes" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <p class="text-sm text-slate-500">Memuat status node…</p>
            </div>

            <h3 class="mt-6 text-sm font-semibold text-slate-300">Database</h3>
            <div id="cluster-databases" class="mt-3 grid gap-3 sm:grid-cols-2">
                <p class="text-sm text-slate-500">Memuat status database…</p>
            </div>

            <p id="cluster-error" class="mt-4 hidden rounded-lg border border-rose-900 bg-rose-950/40 p-3 text-sm text-rose-200"></p>
        </section>

        <script>
            const statusStyles = {
                healthy: 'border-emerald-800 bg-emerald-950/40 text-emerald-200',
                degraded: 'border-amber-800 bg-amber-950/40 text-amber-200',
                unhealthy: 'border-rose-800 bg-rose-950/40 text-rose-200',
                unknown: 'border-slate-700 bg-slate-950 text-slate-300'
            };

            function statusLabel(status) {
                return ({ healthy: 'Sehat', degraded: 'Degradasi', unhealthy: 'Tidak sehat', unknown: 'Tidak diketahui' })[status] || status;
            }

            function makeCard(title, status, details = []) {
                const card = document.createElement('article');
                card.className = 'rounded-xl border border-slate-800 bg-slate-950 p-4';

                const heading = document.createElement('div');
                heading.className = 'flex items-start justify-between gap-3';
                const name = document.createElement('h4');
                name.className = 'font-semibold';
                name.textContent = title;
                const badge = document.createElement('span');
                badge.className = 'rounded-full border px-2 py-1 text-xs ' + (statusStyles[status] || statusStyles.unknown);
                badge.textContent = statusLabel(status);
                heading.append(name, badge);
                card.append(heading);

                details.forEach((detail) => {
                    const line = document.createElement('p');
                    line.className = 'mt-2 text-xs text-slate-400';
                    line.textContent = detail;
                    card.append(line);
                });
                return card;
            }

            async function refreshClusterStatus() {
                const errorBox = document.getElementById('cluster-error');
                try {
                    const response = await fetch(@json(route('admin.cluster-status')), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    if (!response.ok) throw new Error(response.status === 403 ? 'Akses ditolak.' : 'Endpoint monitoring mengembalikan HTTP ' + response.status);
                    const data = await response.json();

                    const nodes = document.getElementById('cluster-nodes');
                    nodes.replaceChildren();
                    data.nodes.forEach((node) => {
                        const details = [];
                        if (node.reported_node_id && node.reported_node_id !== node.id) details.push('ID dilaporkan: ' + node.reported_node_id);
                        if (node.hostname) details.push('Hostname: ' + node.hostname);
                        details.push('Diperiksa: ' + new Date(node.checked_at).toLocaleTimeString());
                        nodes.append(makeCard(node.id, node.status, details));
                    });
                    if (!data.nodes.length) nodes.append(makeCard('Tidak ada node terkonfigurasi', 'unknown'));

                    const databases = document.getElementById('cluster-databases');
                    databases.replaceChildren();
                    const primary = data.databases.primary;
                    databases.append(makeCard('PostgreSQL Primary', primary.status, [
                        'Role terdeteksi: ' + primary.role,
                        primary.error || ('Diperiksa: ' + new Date(primary.checked_at).toLocaleTimeString())
                    ]));
                    const replica = data.databases.replica;
                    const replicaDetails = ['Role terdeteksi: ' + replica.role];
                    if (replica.lag_bytes !== null && replica.lag_bytes !== undefined) replicaDetails.push('Selisih WAL receive/replay: ' + Number(replica.lag_bytes).toLocaleString() + ' byte');
                    replicaDetails.push(replica.error || ('Diperiksa: ' + new Date(replica.checked_at).toLocaleTimeString()));
                    databases.append(makeCard('PostgreSQL Replica', replica.status, replicaDetails));

                    document.getElementById('cluster-checked-at').textContent = 'Pembaruan: ' + new Date(data.checked_at).toLocaleTimeString();
                    errorBox.classList.add('hidden');
                    errorBox.textContent = '';
                } catch (error) {
                    errorBox.textContent = 'Monitoring gagal diperbarui: ' + error.message;
                    errorBox.classList.remove('hidden');
                }
            }

            refreshClusterStatus();
            window.setInterval(refreshClusterStatus, 5000);
        </script>
    </main>
</body>
</html>
