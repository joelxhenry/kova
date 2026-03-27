.PHONY: up down build restart shell artisan migrate fresh test tinker logs pnpm mail setup

# --- Lifecycle ---
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

restart:
	docker compose down && docker compose up -d

# --- App ---
shell:
	docker compose exec app sh

artisan:
	docker compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

test:
	docker compose exec app php artisan test

tinker:
	docker compose exec app php artisan tinker

# --- Frontend ---
pnpm:
	docker compose exec node pnpm $(filter-out $@,$(MAKECMDGOALS))

# --- Logging ---
logs:
	docker compose logs -f

# --- Mail ---
mail:
	@echo "Mailpit UI: http://localhost:9025"

# --- Setup (first run) ---
setup:
	cp .env.docker .env
	docker compose up -d
	@echo ""
	@echo "Kova is running:"
	@echo "  App:     http://localhost:9000"
	@echo "  Vite:    http://localhost:5170"
	@echo "  Mailpit: http://localhost:9025"
	@echo "  MySQL:   localhost:3309"
	@echo "  Redis:   localhost:6380"

# Catch-all to allow passing args to artisan/pnpm
%:
	@:
