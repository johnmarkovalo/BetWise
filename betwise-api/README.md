# BetWise API

## Requirements

- Docker & Docker Compose
- Node.js & npm (for building frontend assets)

## Setup

**1. Clone the repo and copy the env file:**

```bash
cp .env.example .env
```

**2. Update `.env` with the correct values** — see comments inside `.env.example` for guidance.

**3. Install dependencies and build frontend assets:**

```bash
composer install
npm install && npm run build
```

**4. Build and start the containers:**

```bash
docker compose up -d --build
```

**5. Generate the app key and run migrations:**

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

The app will be available at `http://localhost:8080`.

## Containers

| Name | Image | External Port | Purpose |
|---|---|---|---|
| `bw-app` | `betwise-app` | — | PHP 8.4-FPM process manager. Executes the Laravel application and serves responses to Nginx via FastCGI. Does not accept direct external traffic. |
| `bw-nginx` | `nginx:alpine` | `8080` | Web server and reverse proxy. Receives all HTTP requests and forwards PHP files to `bw-app` via FastCGI. Entry point for the application. |
| `bw-horizon` | `betwise-horizon` | — | Runs `php artisan horizon`, which manages and auto-scales all queue workers. Processes background jobs (broadcasting events, sending notifications, etc.) and exposes the Horizon dashboard at `/horizon`. |
| `bw-reverb` | `betwise-reverb` | `8081` | Laravel Reverb WebSocket server. Maintains persistent connections with browser clients and pushes real-time broadcast events to them. |
| `bw-db` | `mariadb:11` | `3306` | MariaDB 11 relational database. Primary data store for all application models. |
| `bw-redis` | `redis:alpine` | `6379` | Redis in-memory store. Used as the queue backend (jobs dispatched here are picked up by `bw-horizon`), cache store, and session store. |
| `bw-adminer` | `adminer:latest` | `8082` | Lightweight web-based database UI. Allows browsing and querying the database directly via browser. |

> **Note:** `bw-horizon` replaces a standalone queue worker — do not run `php artisan queue:work` alongside it, as both would compete to process the same jobs.

## Real-time (Reverb)

The `bw-reverb` and `bw-horizon` containers start automatically with `docker compose up`.

Reverb uses two separate sets of env vars because PHP (inside Docker) and the browser (outside Docker) reach Reverb through different addresses:

```dotenv
# PHP backend — uses Docker internal network
REVERB_HOST=reverb     # Docker service name
REVERB_PORT=8080       # Internal port

# Browser (Laravel Echo) — uses the host machine
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8081  # External Docker port
```

After changing any `VITE_*` var, rebuild assets: `npm run build`.

### Reverb Test Page

Visit `http://localhost:8080/reverb-test` to verify the end-to-end WebSocket connection. Open it in two tabs — broadcasting from one should appear in both instantly.

## Useful Commands

```bash
# Stop containers
docker compose down

# Stop and remove volumes (wipes the database)
docker compose down -v

# Tail logs
docker compose logs -f

# Run Artisan commands
docker compose exec app php artisan <command>
```

## Testing

```bash
# Run all tests
docker compose exec app php artisan test --compact

# Run a specific file
docker compose exec app php artisan test --compact tests/Feature/ExampleTest.php

# Filter by test name
docker compose exec app php artisan test --compact --filter=testName
```

---

## Debugging

### Broadcasting events not received by the browser

**Symptom:** Browser shows "Connected" but events never appear.

**1. Check `BROADCAST_CONNECTION` is set to `reverb`:**
```dotenv
BROADCAST_CONNECTION=reverb
```

**2. Check `REVERB_HOST` uses the Docker service name, not `localhost`:**
```dotenv
REVERB_HOST=reverb
REVERB_PORT=8080
```
Confirm by checking the Laravel log for: `cURL error 7: Failed to connect to localhost port ...`

**3. Ensure Horizon is running:**
`ShouldBroadcast` events are dispatched via the queue — if no worker is running they will be queued but never sent.
```bash
docker compose ps   # bw-horizon should be Up
docker compose up -d horizon   # start it if missing
```

---

### Browser cannot connect to Reverb (status stuck on "Connecting...")

**Symptom:** Connection status never turns green.

**Cause:** `VITE_REVERB_HOST`/`VITE_REVERB_PORT` point to the wrong address. The browser connects from outside Docker, so it needs the externally exposed port.

```dotenv
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8081
```

Rebuild assets after any `VITE_*` change:
```bash
npm run build
```

---

### Production deployment notes

- `REVERB_HOST`/`REVERB_PORT` → internal address PHP containers use to reach Reverb (e.g. a private service name or load balancer DNS).
- `VITE_REVERB_HOST`/`VITE_REVERB_PORT` → public WebSocket address browsers connect to (e.g. `ws.yourdomain.com` on port `443` with `VITE_REVERB_SCHEME=https`).
- Always run `npm run build` after changing `VITE_*` vars before deploying.
