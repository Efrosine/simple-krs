# Simple KRS

A minimal Laravel foundation for the Simple KRS distributed-systems project.

## Phase 1: Reverb / WebSocket proof of concept

Phase 1 adds Laravel Reverb, Laravel Echo, and a small public-channel demo at `/socket-demo`. Open the page in two tabs, confirm both show **Terhubung**, and send a message to see it arrive in both tabs without refreshing.

## Phase 2: Authentication and class catalog

Phase 2 adds session-based login, student registration, a basic admin role, a seeded class catalog, and the initial relational schema for enrollments.

## Phase 3: Atomic enrollment

Phase 3 adds taking and dropping classes, row locking within database transactions, unique constraints against duplicate enrollment, and a public Reverb event for live capacity updates.

## Phase 4: KRS validation

Phase 4 enforces a configurable maximum credit load (24 SKS by default), rejects overlapping class schedules, and serializes KRS changes per student inside database transactions. Set `KRS_MAX_CREDITS` in `.env` to override the default limit. The catalog displays the student's current credit total.

### Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- SQLite for local development (the default configuration)

### Install locally

```bash
git clone https://github.com/Efrosine/simple-krs.git
cd simple-krs
git switch feature/phase-4-krs-validation
composer install
composer update laravel/reverb
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

If the local `.env` already exists from earlier phases, keep it and do not overwrite it. Keep the Reverb settings to use WebSocket updates.

Run the app:

```bash
composer run dev
```

Visit `http://localhost:8000` (or the URL printed by Laravel).

### Demo accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@simple-krs.test` | `password` |
| Student | `student@simple-krs.test` | `password` |

These credentials are for local development only. Do not use them in a deployed environment.

### Phase 3 manual verification

- Log in as a student and take a class. Confirm remaining seats decreases by one.
- Take the same class again. The application should reject the duplicate.
- Log in as another student and try a class after its capacity is reached. The application should reject the request.
- Open the catalog in two browser sessions. Enrollment changes should update the seat counts through Reverb.
- Drop an enrolled class. Confirm the remaining seat becomes available again.
- No test files are added, per project instruction.

### Phase 4 manual verification

- Set `KRS_MAX_CREDITS=24` in `.env` (or choose a lower limit for a quicker check), then refresh Laravel config if config caching is enabled.
- Enroll in classes until the next class would exceed the SKS limit. The request should be rejected with a clear explanation.
- Try enrolling in two classes on the same day with overlapping times. The second request should be rejected and name the conflicting class.
- Try classes on the same day with non-overlapping times. They should be allowed if capacity and the credit limit permit it.
- Drop a class, then confirm its credits no longer count toward the student's SKS total.
- Check the behavior using PostgreSQL for row-lock and concurrent-request verification. SQLite does not prove PostgreSQL row-lock behavior.

### Scope limits

The enrollment transaction protects capacity on a single database connection that supports row-level locking. SQLite is suitable for local feature checks, but does not provide the same row-locking behavior as PostgreSQL or MySQL. Distributed database replication and cross-node consistency remain future phases.


## Phase 5: PostgreSQL primary and replica

Phase 5 keeps the default `pgsql` connection pointed at the primary. All enrollment transactions, session/cache/queue database operations, and other writes continue to use the primary. Only the class catalog's course list and enrollment counts use the dedicated `pgsql_replica` connection.

For a local PostgreSQL replication demo, configure `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=simple_krs
DB_USERNAME=postgres
DB_PASSWORD=your-primary-password

DB_READ_HOST=127.0.0.1
DB_READ_PORT=5433
DB_READ_USERNAME=postgres
DB_READ_PASSWORD=your-replica-password
DB_READ_TIMEOUT=2
```

Adjust hostnames and ports to match the Docker network. When Laravel runs in a container attached to `db-net`, use the service name and internal port (for example `postgres:5432` and `postgres-replica:5432`), not the host-published ports.

Run migrations and seeders against the primary only:

```bash
php artisan config:clear
php artisan migrate --seed
```

The catalog falls back to the primary if the replica query fails and logs a warning. The student's enrolled-course list is read from the primary to preserve read-your-writes behavior. Catalog enrollment counts can temporarily lag behind because they are intentionally served from the replica. Enrollment capacity, SKS, schedule-conflict validation, and all writes remain on the primary.

### Phase 5 manual verification

- Confirm the primary is writable and the replica is in recovery/read-only mode.
- Open the catalog and inspect PostgreSQL activity or query logs to confirm catalog queries reach the replica.
- Enroll in a class and immediately confirm it appears in “KRS saya” and contributes to the student's SKS total, even if the replica is delayed.
- Introduce a controlled replication delay and confirm catalog counts may lag while enrollment validation remains safe.
- Stop the replica. The catalog should fall back to the primary; a warning should appear in the Laravel log.
- Start the replica again and confirm streaming replication resumes.
- No test files are added, per project instruction.
