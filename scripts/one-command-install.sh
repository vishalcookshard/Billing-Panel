#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "$0")/.." && pwd)
cd "$ROOT_DIR"

echo "Starting one-command installer for Billing-Panel"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is required. Please install Docker and docker-compose." >&2
  exit 1
fi

echo "Bringing up containers (build if needed)..."
docker compose up -d --build

echo "Waiting for app container to become healthy..."
APP_CONTAINER=billing-panel-app
for i in {1..60}; do
  STATUS=$(docker inspect -f '{{.State.Health.Status}}' "$APP_CONTAINER" 2>/dev/null || echo "unknown")
  if [ "$STATUS" = "healthy" ]; then
    echo "App container healthy"
    break
  fi
  echo "Waiting for app (status=$STATUS), retrying..."
  sleep 2
done

echo "Running migrations and seeding inside app container..."
docker compose exec -T app php artisan migrate --force || true
docker compose exec -T app php artisan db:seed --force || true

echo "One-command install complete. Services started: web, app, worker, scheduler, db, redis"
echo "Admin user: admin@example.com / password (change immediately)"

echo "To view logs: docker compose logs -f"
