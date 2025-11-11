#!/bin/bash

set -e

echo "🚀 Starting deployment..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Pull latest code (skip if already pulled)
echo -e "${YELLOW}📥 Pulling latest code...${NC}"
if [ "$1" != "--skip-pull" ]; then
    git pull origin main || git pull origin master || echo "⚠️  Could not pull, continuing with current code..."
else
    echo "⏭️  Skipping git pull (--skip-pull flag)"
fi

# Step 2: Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ .env file not found!${NC}"
    echo "Please create .env file from .env.example"
    exit 1
fi

# Step 3: Rebuild images (if Dockerfile or dependencies changed)
echo -e "${YELLOW}🔨 Building Docker images...${NC}"
if [ "$1" == "--fast" ] || [ "$2" == "--fast" ]; then
    echo "⚡ Fast mode: Skipping rebuild (use for code-only changes)"
else
    docker compose build --no-cache app worker
fi

# Step 4: Stop and remove old containers (skip if fast mode)
if [ "$1" == "--fast" ] || [ "$2" == "--fast" ]; then
    echo "⚡ Fast mode: Restarting containers without down/up"
    docker compose restart app worker web
else
    echo -e "${YELLOW}🛑 Stopping containers...${NC}"
    docker compose down
    
    # Step 5: Start containers
    echo -e "${YELLOW}▶️  Starting containers...${NC}"
    docker compose up -d
fi

# Step 6: Wait for services to be ready
echo -e "${YELLOW}⏳ Waiting for services to be ready...${NC}"
sleep 10

# Step 7: Run migrations
echo -e "${YELLOW}🔄 Running migrations...${NC}"
docker compose exec -T app php artisan migrate --force || true

# Step 8: Clear and cache config
echo -e "${YELLOW}🧹 Clearing cache...${NC}"
docker compose exec -T app php artisan config:clear || true
docker compose exec -T app php artisan route:clear || true
docker compose exec -T app php artisan view:clear || true
docker compose exec -T app php artisan cache:clear || true

# Step 9: Rebuild cache
echo -e "${YELLOW}💾 Rebuilding cache...${NC}"
docker compose exec -T app php artisan config:cache || true
docker compose exec -T app php artisan route:cache || true
docker compose exec -T app php artisan view:cache || true

# Step 10: Check status
echo -e "${YELLOW}📊 Checking container status...${NC}"
docker compose ps

echo -e "${GREEN}✅ Deployment completed!${NC}"
echo -e "${GREEN}🌐 Application is available at: http://your-vps-ip:8080${NC}"

