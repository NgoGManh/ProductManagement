# Deployment (Docker)

## Prerequisites

- Docker Engine 24+ and Docker Compose v2 on your VPS

## What’s included

- PHP-FPM 8.4 container with Composer and built assets (multi-stage)
- Nginx container serving `public/`
- MySQL 8 container with persistent volume
- Worker container running queue:work and schedule:work
- Entry point auto-runs key:generate, migrate, caches

## Quick start

1. Create .env for production (or copy from example)

```bash
cp .env.example .env
```

Set important values:

- APP_ENV=production
- APP_DEBUG=false
- APP_URL=http://your-domain-or-ip
- DB_HOST=db
- DB_PORT=3306
- DB_DATABASE=product
- DB_USERNAME=product
- DB_PASSWORD=product
- QUEUE_CONNECTION=database
- FILESYSTEM_DISK=local (or your R2 disk config)

2. Build and start

```bash
docker compose up -d --build
```

3. Access the app

- Nginx: http://your-vps-ip:8080

## Common commands

- Tail logs:

```bash
docker compose logs -f web app worker db
```

- Run artisan:

```bash
docker compose exec app php artisan <command>
```

- Rebuild assets (if you change frontend):

```bash
docker compose build app
docker compose up -d app web
```

## Notes

- The `app` image builds assets during image build via Node stage; for hot changes, rebuild the image or mount your built `public/build` if you prebuild locally.
- Queue and Scheduler run in the `worker` service managed by Supervisor.
- Nginx maps port 8080; adjust in docker-compose.yml if you need 80/443 (behind a reverse proxy like Caddy/Nginx/Traefik).
