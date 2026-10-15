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

## Phase 6: Application node identity, Docker, and service discovery

Phase 6 adds a Docker image for Laravel, configurable application-node identity, an `X-App-Node` response header, request log context, and an unauthenticated `/api/node` endpoint for inspecting the instance serving a request.

The `Dockerfile` uses a multi-stage build for Vite assets and PHP dependencies. It installs the PostgreSQL PDO extension and runs Laravel's built-in HTTP server on port 8000. This is intended for the local multi-container learning environment, not as a recommendation to use PHP's built-in server for a production deployment.

Keep the infrastructure Compose outside this repository. The intended local topology is three Laravel containers, a dedicated Traefik container using the Docker provider, and the existing PostgreSQL Primary/Replica services. Traefik discovers app containers through Docker labels instead of a manually maintained list of container IPs. If the existing Traefik already owns host port 80, publish the dedicated Simple KRS Traefik on a different host port, such as 8081.

Build the app image from the repository directory:

```bash
docker build -t simple-krs:phase6 .
```

Each application instance must have a unique `APP_NODE_ID`, while sharing the same `APP_KEY`, database, session backend, and relevant broadcast configuration. For example, use `app-1`, `app-2`, and `app-3`. Never generate a separate `APP_KEY` for each node.

When app containers share the PostgreSQL Compose network, configure database connections using Docker service names and internal ports:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=simple_krs
DB_USERNAME=krs_admin
DB_PASSWORD=your-primary-password
DB_READ_HOST=postgres-replica
DB_READ_PORT=5432
DB_READ_USERNAME=krs_admin
DB_READ_PASSWORD=your-primary-password
```

The exact external Docker network names must match the networks actually created by the database and existing infrastructure Compose projects. Do not use host-published PostgreSQL ports from one container to connect to another container on the same Docker network.

Run migrations and seeders once against the Primary, not independently on each application node. For a fresh development database only, `php artisan migrate:fresh --seed` is destructive and drops existing tables.

The `/api/node` endpoint returns node identity and hostname. The global middleware adds `X-App-Node` to HTTP responses and records the node ID, method, path, and response status in logs. Laravel's `/up` endpoint is available as a health-check endpoint.

### Phase 6 manual verification

- Build the image successfully with `docker build -t simple-krs:phase6 .`.
- Run three app containers with unique `APP_NODE_ID` values and shared application/database/session configuration.
- Access `/api/node` through the dedicated Traefik address and inspect both the JSON node ID and `X-App-Node` response header.
- Confirm requests are distributed among app instances.
- Stop one app container and confirm Traefik stops sending new requests to the unhealthy instance while other instances remain available.
- Restart the stopped instance and confirm it is discovered again.
- Inspect container logs for the node identity, HTTP method, path, and response status.
- Verify WebSocket/Reverb routing separately; HTTP load balancing alone does not make Reverb events shared across multiple Reverb servers.
- No test files are added, per project instruction.

## Phase 7: Cluster monitoring

The admin dashboard at `/admin` now includes a live cluster-monitoring panel. It polls the admin-only `/admin/cluster-status` endpoint every five seconds and reports:

- Application node health using each configured node's `/api/node` endpoint.
- PostgreSQL Primary connectivity and detected recovery role.
- PostgreSQL Replica connectivity, detected recovery role, and the byte difference between received and replayed WAL when available.
- The last successful monitoring refresh time.

The node list uses Docker DNS service names rather than fixed container IPs. The default list is `app-1=http://app-1:8000,app-2=http://app-2:8000,app-3=http://app-3:8000`. Override it in the infrastructure Compose environment with `APP_CLUSTER_NODES` if the service names or number of nodes differ. Example:

```dotenv
APP_CLUSTER_NODES=app-1=http://app-1:8000,app-2=http://app-2:8000,app-3=http://app-3:8000
```

This endpoint is restricted to authenticated admin accounts. An unreachable node or database is shown as unhealthy instead of hiding the failure. The dashboard currently does not report active Reverb/WebSocket connection counts or a cross-node event audit stream; those require a Reverb metrics source and shared event storage, and are not inferred from HTTP health checks.

### Phase 7 manual verification

- Open `/admin` using an admin account and confirm all three app nodes and both database connections are shown.
- Stop one app container and confirm its card changes to unhealthy after the next poll; start it again and confirm recovery.
- Stop the replica and confirm the replica card changes to unhealthy while the dashboard remains available through the primary.
- Check that a student account receives HTTP 403 from `/admin/cluster-status`.
- Confirm `APP_CLUSTER_NODES` uses internal service DNS names and no fixed IP addresses.

