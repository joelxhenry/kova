# Docker Development Environment — Implementation Plan

**Project:** Kova (Laravel 13 + Vue 3 + Inertia.js)
**Goal:** Containerized dev environment with MySQL, Redis, and Mailpit

---

## Services Overview

| Service | Image | Port (host:container) | Purpose |
|---------|-------|----------------------|---------|
| **app** | Custom (PHP 8.4-FPM) | — | Laravel application |
| **web** | nginx:alpine | 9000:80 | HTTP server |
| **node** | node:22-alpine | 5170:5170 | Vite dev server |
| **mysql** | mysql:8.4 | 3309:3306 | Primary database |
| **redis** | redis:7-alpine | 6380:6379 | Cache, sessions, queues |
| **mailpit** | axllent/mailpit | 9025:8025, 1025:1025 | Email capture & UI |

---

## Phase 1 — Core Infrastructure

Create the foundational Docker files and get the Laravel app running.

### Files to Create

- [x] `Dockerfile` — Multi-stage build for PHP 8.4-FPM
  - Base image: `php:8.4-fpm-alpine`
  - Install extensions: `pdo_mysql`, `redis` (via pecl), `bcmath`, `gd`, `zip`, `intl`, `pcntl`
  - Install Composer from official image
  - Set working directory to `/var/www/html`
  - Copy application code, run `composer install`
  - Configure PHP-FPM pool settings for development (error display, xdebug-ready)

- [x] `docker/nginx/default.conf` — Nginx site config
  - Proxy PHP requests to `app:9000` via FastCGI
  - Serve static assets from `/var/www/html/public`
  - Set `client_max_body_size` to 64M (receipt uploads)

- [x] `docker-compose.yml` — Define all services
  - `app` service: build from Dockerfile, mount project as volume
  - `web` service: nginx, depends on `app`
  - `mysql` service: named volume for data persistence
  - Environment variables via `.env` file

- [x] `.dockerignore` — Exclude vendor, node_modules, .git, storage logs

### Environment Changes

- [x] Create `.env.docker` template with Docker-specific defaults:
  ```
  DB_CONNECTION=mysql
  DB_HOST=mysql
  DB_PORT=3306
  DB_DATABASE=kova
  DB_USERNAME=kova
  DB_PASSWORD=secret
  ```

### Verification

- [x] `docker compose up -d` starts all containers without errors
- [x] `php artisan migrate` runs successfully against MySQL
- [x] Visiting `http://localhost:9000` renders the Dashboard page

---

## Phase 2 — Redis for Cache, Sessions & Queues

Switch from database-backed drivers to Redis.

### Configuration Changes

- [x] Add Redis service to `docker-compose.yml`
  - Image: `redis:7-alpine`
  - Named volume for persistence
  - Healthcheck: `redis-cli ping`

- [x] Update `.env.docker`:
  ```
  CACHE_STORE=redis
  SESSION_DRIVER=redis
  QUEUE_CONNECTION=redis
  REDIS_HOST=redis
  REDIS_PORT=6379
  ```

- [x] Ensure `phpredis` extension is installed in Dockerfile (Phase 1 already covers this)

### Verification

- [x] `php artisan tinker` → `Cache::put('test', 'ok', 60)` → `Cache::get('test')` returns `'ok'`
- [x] Login/session persists across requests (session stored in Redis)
- [x] `redis-cli` inside container shows keys being written

---

## Phase 3 — Mailpit for Email Testing

Capture all outgoing email in a local web UI.

### Configuration Changes

- [x] Add Mailpit service to `docker-compose.yml`
  - Image: `axllent/mailpit:latest`
  - Ports: `9025:8025` (web UI), SMTP on internal port 1025
  - No volume needed (ephemeral is fine for dev)

- [x] Update `.env.docker`:
  ```
  MAIL_MAILER=smtp
  MAIL_HOST=mailpit
  MAIL_PORT=1025
  MAIL_FROM_ADDRESS=no-reply@kova.local
  MAIL_FROM_NAME=Kova
  ```

### Verification

- [x] `php artisan tinker` → `Mail::raw('Test', fn($m) => $m->to('test@example.com'))` sends without error
- [x] Email appears in Mailpit UI at `http://localhost:9025`

---

## Phase 4 — Node / Vite Dev Server

Hot-reload frontend development inside Docker.

### Configuration Changes

- [x] Add `node` service to `docker-compose.yml`
  - Image: `node:22-alpine`
  - Working dir: `/var/www/html`
  - Mount project as volume
  - Command: `sh -c "corepack enable && pnpm install && pnpm run dev --host 0.0.0.0"`
  - Ports: `5170:5170`
  - Depends on: `app`

- [x] Update `vite.config.js` — Add HMR config for Docker:
  ```js
  server: {
      host: '0.0.0.0',
      hmr: {
          host: 'localhost',
      },
  }
  ```

- [x] Update `.env.docker`:
  ```
  VITE_DEV_SERVER_URL=http://localhost:5170
  ```

### Verification

- [x] `pnpm run dev` starts inside container, HMR websocket connects in browser
- [x] Editing a `.vue` file triggers hot reload without full page refresh

---

## Phase 5 — Queue Worker & Orchestration

Add a dedicated queue worker and streamline the full stack.

### Configuration Changes

- [x] Add `queue` service to `docker-compose.yml`
  - Reuse `app` image (no separate build)
  - Command: `php artisan queue:work redis --tries=3 --timeout=90`
  - Depends on: `app`, `redis`, `mysql`
  - Restart policy: `unless-stopped`
  - No port exposure needed

- [x] Add healthchecks to all services:
  - `app`: `php-fpm -t`
  - `mysql`: `mysqladmin ping -h 127.0.0.1`
  - `redis`: `redis-cli ping`
  - `mailpit`: HTTP check on port 9025

- [x] Add `depends_on` with `condition: service_healthy` to enforce startup order:
  `mysql` + `redis` → `app` → `web`, `queue`, `node`

### Verification

- [x] `docker compose up -d` brings up all 7 services in correct order
- [x] Dispatching a job from tinker processes via the queue worker
- [x] `docker compose down` cleanly stops everything; `docker compose up -d` restores state (MySQL data persisted)

---

## Phase 6 — Developer Experience

Make it seamless to onboard and work daily.

### Files to Create

- [x] `Makefile` with common shortcuts:
  ```makefile
  up:          docker compose up -d
  down:        docker compose down
  build:       docker compose build --no-cache
  shell:       docker compose exec app sh
  artisan:     docker compose exec app php artisan
  migrate:     docker compose exec app php artisan migrate
  fresh:       docker compose exec app php artisan migrate:fresh --seed
  test:        docker compose exec app php artisan test
  tinker:      docker compose exec app php artisan tinker
  logs:        docker compose logs -f
  pnpm:        docker compose exec node pnpm
  ```

- [x] `docker/app/entrypoint.sh` — Container entrypoint script:
  - Wait for MySQL to be ready
  - Run `php artisan migrate --force` on startup
  - Generate app key if missing
  - Set correct storage permissions
  - Start PHP-FPM

- [x] Update `.gitignore` to include:
  ```
  docker/mysql/data/
  ```

### Verification

- [x] Fresh clone → `cp .env.docker .env && make up` → working app within 2 minutes
- [x] `make test` runs Pest test suite inside the container
- [x] `make shell` drops into the app container

---

## Final `docker-compose.yml` Structure

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: kova-app
    volumes:
      - .:/var/www/html
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy
    env_file: .env
    healthcheck:
      test: ["CMD-SHELL", "php-fpm -t 2>/dev/null"]
      interval: 10s
      timeout: 5s
      retries: 3

  web:
    image: nginx:alpine
    ports:
      - "9000:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      app:
        condition: service_healthy

  mysql:
    image: mysql:8.4
    ports:
      - "3309:3306"
    environment:
      MYSQL_DATABASE: kova
      MYSQL_USER: kova
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1"]
      interval: 5s
      timeout: 5s
      retries: 10

  redis:
    image: redis:7-alpine
    ports:
      - "6380:6379"
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 5s
      retries: 5

  mailpit:
    image: axllent/mailpit:latest
    ports:
      - "9025:8025"
    healthcheck:
      test: ["CMD", "wget", "--spider", "-q", "http://localhost:8025"]
      interval: 10s
      timeout: 5s
      retries: 3

  node:
    image: node:22-alpine
    working_dir: /var/www/html
    volumes:
      - .:/var/www/html
      - node_modules:/var/www/html/node_modules
    command: sh -c "corepack enable && pnpm install && pnpm run dev --host 0.0.0.0 --port 5170"
    ports:
      - "5170:5170"
    depends_on:
      app:
        condition: service_healthy

  queue:
    image: kova-app
    command: ["php", "artisan", "queue:work", "redis", "--tries=3", "--timeout=90"]
    volumes:
      - .:/var/www/html
    depends_on:
      app:
        condition: service_healthy
    env_file: .env
    restart: unless-stopped

volumes:
  mysql_data:
  redis_data:
  node_modules:
```

---

## Port Reference (Development)

| URL | Service |
|-----|---------|
| `http://localhost:9000` | Kova application |
| `http://localhost:5170` | Vite HMR dev server |
| `http://localhost:9025` | Mailpit email UI |
| `localhost:3309` | MySQL (DB clients) |
| `localhost:6380` | Redis (redis-cli) |
