# Docker Development Environment — Implementation Plan

**Project:** Kova (Laravel 13 + Vue 3 + Inertia.js)
**Goal:** Containerized dev environment with MySQL, Redis, and Mailpit

---

## Services Overview

| Service | Image | Port (host:container) | Purpose |
|---------|-------|----------------------|---------|
| **app** | Custom (PHP 8.4-FPM) | — | Laravel application |
| **web** | nginx:alpine | 8000:80 | HTTP server |
| **node** | node:22-alpine | 5173:5173 | Vite dev server |
| **mysql** | mysql:8.4 | 3306:3306 | Primary database |
| **redis** | redis:7-alpine | 6379:6379 | Cache, sessions, queues |
| **mailpit** | axllent/mailpit | 8025:8025, 1025:1025 | Email capture & UI |

---

## Phase 1 — Core Infrastructure

Create the foundational Docker files and get the Laravel app running.

### Files to Create

- [ ] `Dockerfile` — Multi-stage build for PHP 8.4-FPM
  - Base image: `php:8.4-fpm-alpine`
  - Install extensions: `pdo_mysql`, `redis` (via pecl), `bcmath`, `gd`, `zip`, `intl`, `pcntl`
  - Install Composer from official image
  - Set working directory to `/var/www/html`
  - Copy application code, run `composer install`
  - Configure PHP-FPM pool settings for development (error display, xdebug-ready)

- [ ] `docker/nginx/default.conf` — Nginx site config
  - Proxy PHP requests to `app:9000` via FastCGI
  - Serve static assets from `/var/www/html/public`
  - Set `client_max_body_size` to 64M (receipt uploads)

- [ ] `docker-compose.yml` — Define all services
  - `app` service: build from Dockerfile, mount project as volume
  - `web` service: nginx, depends on `app`
  - `mysql` service: named volume for data persistence
  - Environment variables via `.env` file

- [ ] `.dockerignore` — Exclude vendor, node_modules, .git, storage logs

### Environment Changes

- [ ] Create `.env.docker` template with Docker-specific defaults:
  ```
  DB_CONNECTION=mysql
  DB_HOST=mysql
  DB_PORT=3306
  DB_DATABASE=kova
  DB_USERNAME=kova
  DB_PASSWORD=secret
  ```

### Verification

- [ ] `docker compose up -d` starts all containers without errors
- [ ] `php artisan migrate` runs successfully against MySQL
- [ ] Visiting `http://localhost:8000` renders the Dashboard page

---

## Phase 2 — Redis for Cache, Sessions & Queues

Switch from database-backed drivers to Redis.

### Configuration Changes

- [ ] Add Redis service to `docker-compose.yml`
  - Image: `redis:7-alpine`
  - Named volume for persistence
  - Healthcheck: `redis-cli ping`

- [ ] Update `.env.docker`:
  ```
  CACHE_STORE=redis
  SESSION_DRIVER=redis
  QUEUE_CONNECTION=redis
  REDIS_HOST=redis
  REDIS_PORT=6379
  ```

- [ ] Ensure `phpredis` extension is installed in Dockerfile (Phase 1 already covers this)

### Verification

- [ ] `php artisan tinker` → `Cache::put('test', 'ok', 60)` → `Cache::get('test')` returns `'ok'`
- [ ] Login/session persists across requests (session stored in Redis)
- [ ] `redis-cli` inside container shows keys being written

---

## Phase 3 — Mailpit for Email Testing

Capture all outgoing email in a local web UI.

### Configuration Changes

- [ ] Add Mailpit service to `docker-compose.yml`
  - Image: `axllent/mailpit:latest`
  - Ports: `8025:8025` (web UI), `1025:1025` (SMTP)
  - No volume needed (ephemeral is fine for dev)

- [ ] Update `.env.docker`:
  ```
  MAIL_MAILER=smtp
  MAIL_HOST=mailpit
  MAIL_PORT=1025
  MAIL_FROM_ADDRESS=no-reply@kova.local
  MAIL_FROM_NAME=Kova
  ```

### Verification

- [ ] `php artisan tinker` → `Mail::raw('Test', fn($m) => $m->to('test@example.com'))` sends without error
- [ ] Email appears in Mailpit UI at `http://localhost:8025`

---

## Phase 4 — Node / Vite Dev Server

Hot-reload frontend development inside Docker.

### Configuration Changes

- [ ] Add `node` service to `docker-compose.yml`
  - Image: `node:22-alpine`
  - Working dir: `/var/www/html`
  - Mount project as volume
  - Command: `sh -c "corepack enable && pnpm install && pnpm run dev --host 0.0.0.0"`
  - Ports: `5173:5173`
  - Depends on: `app`

- [ ] Update `vite.config.js` — Add HMR config for Docker:
  ```js
  server: {
      host: '0.0.0.0',
      hmr: {
          host: 'localhost',
      },
  }
  ```

- [ ] Update `.env.docker`:
  ```
  VITE_DEV_SERVER_URL=http://localhost:5173
  ```

### Verification

- [ ] `pnpm run dev` starts inside container, HMR websocket connects in browser
- [ ] Editing a `.vue` file triggers hot reload without full page refresh

---

## Phase 5 — Queue Worker & Orchestration

Add a dedicated queue worker and streamline the full stack.

### Configuration Changes

- [ ] Add `queue` service to `docker-compose.yml`
  - Reuse `app` image (no separate build)
  - Command: `php artisan queue:work redis --tries=3 --timeout=90`
  - Depends on: `app`, `redis`, `mysql`
  - Restart policy: `unless-stopped`
  - No port exposure needed

- [ ] Add healthchecks to all services:
  - `app`: `php-fpm -t`
  - `mysql`: `mysqladmin ping -h 127.0.0.1`
  - `redis`: `redis-cli ping`
  - `mailpit`: HTTP check on port 8025

- [ ] Add `depends_on` with `condition: service_healthy` to enforce startup order:
  `mysql` + `redis` → `app` → `web`, `queue`, `node`

### Verification

- [ ] `docker compose up -d` brings up all 6 services in correct order
- [ ] Dispatching a job from tinker processes via the queue worker
- [ ] `docker compose down` cleanly stops everything; `docker compose up -d` restores state (MySQL data persisted)

---

## Phase 6 — Developer Experience

Make it seamless to onboard and work daily.

### Files to Create

- [ ] `Makefile` with common shortcuts:
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

- [ ] `docker/app/entrypoint.sh` — Container entrypoint script:
  - Wait for MySQL to be ready
  - Run `php artisan migrate --force` on startup
  - Generate app key if missing
  - Set correct storage permissions
  - Start PHP-FPM

- [ ] Update `.gitignore` to include:
  ```
  docker/mysql/data/
  ```

### Verification

- [ ] Fresh clone → `cp .env.docker .env && make up` → working app within 2 minutes
- [ ] `make test` runs Pest test suite inside the container
- [ ] `make shell` drops into the app container

---

## Final `docker-compose.yml` Structure

```yaml
services:
  app:
    build: .
    volumes:
      - .:/var/www/html
      - /var/www/html/vendor  # anonymous volume, don't overwrite container vendor
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy
    env_file: .env

  web:
    image: nginx:alpine
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  mysql:
    image: mysql:8.4
    ports:
      - "3306:3306"
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
      retries: 5

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      retries: 5

  mailpit:
    image: axllent/mailpit:latest
    ports:
      - "8025:8025"
      - "1025:1025"

  node:
    image: node:22-alpine
    working_dir: /var/www/html
    volumes:
      - .:/var/www/html
      - node_modules:/var/www/html/node_modules
    command: sh -c "corepack enable && pnpm install && pnpm run dev --host 0.0.0.0"
    ports:
      - "5173:5173"
    depends_on:
      - app

  queue:
    build: .
    command: php artisan queue:work redis --tries=3 --timeout=90
    volumes:
      - .:/var/www/html
      - /var/www/html/vendor
    depends_on:
      mysql:
        condition: service_healthy
      redis:
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
| `http://localhost:8000` | Kova application |
| `http://localhost:5173` | Vite HMR dev server |
| `http://localhost:8025` | Mailpit email UI |
| `localhost:3306` | MySQL (DB clients) |
| `localhost:6379` | Redis (redis-cli) |
