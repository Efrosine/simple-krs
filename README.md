# Simple KRS

A minimal Laravel foundation for the Simple KRS distributed-systems project.

## Phase 1: Reverb / WebSocket proof of concept

Phase 1 adds Laravel Reverb, Laravel Echo, and a small public-channel demo at `/socket-demo`. Open the page in two tabs, confirm both show **Terhubung**, and send a message to see it arrive in both tabs without refreshing.

## Phase 2: Authentication and class catalog

Phase 2 adds session-based login, student registration, a basic admin role, a seeded class catalog, and the initial relational schema for enrollments.

### Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- SQLite for local development (the default configuration)

### Install locally

```bash
git clone https://github.com/Efrosine/simple-krs.git
cd simple-krs
git switch feature/phase-2-auth-catalog
composer install
composer update laravel/reverb
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

If the local `.env` already exists from Phase 1, keep it and do not overwrite it. Keep the Reverb settings from that phase if you want to use `/socket-demo`.

Run the app:

```bash
composer run dev
```

Visit `http://localhost:8000` (or the URL printed by Laravel).

### Demo accounts

The seeder creates local-only demo accounts:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@simple-krs.test` | `password` |
| Student | `student@simple-krs.test` | `password` |

These credentials are for local development only. Do not use them in a deployed environment.

### Phase 2 scope

- New registrations are always assigned the `student` role.
- The admin dashboard is protected by a role check.
- The class catalog shows schedule, lecturer, credits, capacity, and remaining seats.
- Enrollment tables are prepared with uniqueness constraints, but taking or dropping classes is not implemented yet.
- Database replication, atomic enrollment, service discovery, and cluster monitoring remain future phases.
- No test files are added, per project instruction.
