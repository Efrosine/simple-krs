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
git switch feature/phase-6-service-discovery
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

The enrollment transaction protects capacity on a single database connection that supports row-level locking. SQLite is suitable for local feature checks, but does not provide the same row-locking behavior as PostgreSQL or MySQL. PostgreSQL replication and read/write consistency are covered in Phase 5 and must be verified with the dedicated primary/replica setup.

## Phase 5: PostgreSQL primary and replica

Phase 5 keeps the default `pgsql` connection pointed at the primary. All enrollment transactions, session/cache/queue database operations, and other writes continue to use the primary. Only the class catalog's course list and enrollment counts use the dedicated `pgsql_replica` connection.

For the dedicated local PostgreSQL Compose, configure Laravel's `.env` with the host-published ports:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=simple_krs
DB_USERNAME=krs_admin
DB_PASSWORD=your-primary-password

DB_READ_HOST=127.0.0.1
DB_READ_PORT=5434
DB_READ_USERNAME=krs_admin
DB_READ_PASSWORD=your-primary-password
```

When Laravel runs in a container on the same Docker network, use the service names and internal port 5432 instead of host-published ports.

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

## Phase 6: Application node identity and service discovery

Phase 6 prepares the Laravel app to run behind a service-discovery-aware load balancer. Each application instance has a configurable `APP_NODE_ID` (falling back to the container hostname), adds its identity to the `X-App-Node` response header, and includes `app_node` in Laravel log context. HTTP requests are logged with method, path, and response status, without logging request bodies or credentials.

The `/api/node` endpoint returns the current instance identity and a simple status payload. Laravel's built-in `/up` endpoint is the health-check endpoint for the load balancer. The Docker Compose and load-balancer configuration are kept outside this repository; use Docker service DNS rather than fixed container IP addresses.

For each application instance, set a unique value such as:

```dotenv
APP_NODE_ID=app-1
```

All app instances must share the same `APP_KEY`, session backend, database configuration, and relevant Reverb settings. Do not generate a different application key for each node, or encrypted sessions/cookies will not be compatible between instances.

### Phase 6 manual verification

- Run at least two app instances behind the load balancer and configure a unique `APP_NODE_ID` for each.
- Request `/api/node` repeatedly through the load balancer. The JSON `node_id` and `X-App-Node` header should show which instance served each request.
- Request `/up` to confirm the health-check endpoint returns a successful response while the instance is healthy.
- Inspect Laravel logs and confirm each request includes the corresponding `app_node`, method, path, and status.
- Stop one app instance. The load balancer should stop routing new requests to it after its health check fails; other healthy instances should continue serving requests.
- Restart the instance and confirm it returns to service after becoming healthy.
- Use Docker service names for service-to-service connections; do not hard-code container IP addresses.
- No test files are added, per project instruction.
