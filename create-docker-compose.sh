#!/bin/bash

# Script to create docker-compose.yml file

cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: product-management-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./storage:/var/www/html/storage
      - ./database:/var/www/html/database
      - ./public:/var/www/html/public
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=${APP_ENV:-production}
      - APP_DEBUG=${APP_DEBUG:-false}
      - APP_URL=${APP_URL:-http://localhost:8000}
      - DB_CONNECTION=sqlite
      - DB_DATABASE=/var/www/html/database/database.sqlite
      - CACHE_DRIVER=file
      - QUEUE_CONNECTION=sync
      - SESSION_DRIVER=file
      - LOG_CHANNEL=stderr
    networks:
      - app-network
    healthcheck:
      test: ["CMD", "php", "-r", "echo 'OK';"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

networks:
  app-network:
    driver: bridge
EOF

echo "✅ docker-compose.yml created successfully!"
echo "📝 File size: $(wc -l < docker-compose.yml) lines"
echo "🔍 Validating..."
docker-compose config > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ File is valid!"
else
    echo "❌ File validation failed. Please check the content."
    exit 1
fi

