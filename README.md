# Simple KRS

A minimal Laravel foundation for the Simple KRS distributed-systems project.

## Phase 1: Reverb / WebSocket proof of concept

This phase adds Laravel Reverb, Laravel Echo, and a small public-channel demo. It deliberately does not implement KRS enrollment, authentication, database replication, or production hardening.

### Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- SQLite (the default local database)

### Install locally

```bash
git clone https://github.com/Efrosine/simple-krs.git
cd simple-krs
git switch feature/phase-1-reverb-socket
composer install
composer update laravel/reverb
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

The Composer update resolves Reverb and writes the matching local lock file because this phase introduces a Composer dependency. Run it once after checking out this branch.

The committed Reverb config is already present. Do not run `php artisan reverb:install` unless you intentionally want to regenerate it; that command may overwrite configuration. Check that the local `.env` has the values below:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local-app-id
REVERB_APP_KEY=local-app-key
REVERB_APP_SECRET=local-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY=${REVERB_APP_KEY}
VITE_REVERB_HOST=${REVERB_HOST}
VITE_REVERB_PORT=${REVERB_PORT}
VITE_REVERB_SCHEME=${REVERB_SCHEME}
```

Build the frontend once:

```bash
npm run build
```

Open three terminals from the project directory:

**Terminal 1: Laravel**
```bash
php artisan serve
```

**Terminal 2: Reverb WebSocket server**
```bash
php artisan reverb:start --debug
```

**Terminal 3: Optional Vite dev server** (use this instead of rebuilding every frontend edit)
```bash
npm run dev
```

Visit http://127.0.0.1:8000/socket-demo. If using Vite, keep its terminal running. Open the same URL in two browser tabs, confirm both show **Terhubung**, send a message from one tab, and confirm it appears in both tabs without refreshing. Also check the Reverb terminal for the connection and broadcast activity.

### Troubleshooting

- **Connection refused:** make sure `php artisan reverb:start --debug` is running on port 8080.
- **Not connected:** clear cached config with `php artisan config:clear`, verify the `REVERB_*` and `VITE_REVERB_*` values in `.env`, then restart Laravel, Reverb, and Vite.
- **Frontend env changes not picked up:** restart `npm run dev` or run `npm run build` again.
- **Missing Reverb command:** run `composer update laravel/reverb`, then `php artisan list` and confirm the Reverb commands are available.

## Scope notes

- `/socket-demo` is a development-only proof of concept using a public channel. Do not send personal data.
- The HTTP endpoint validates the message and broadcasts immediately, so no queue worker is needed for this demo.
- No test files are added, per project instruction.
