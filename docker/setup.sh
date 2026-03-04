#!/bin/bash

echo "========================================="
echo "Translation Management Service - Docker Setup"
echo "========================================="
echo ""

# Change to project root
cd ..

# Stop and remove existing containers
echo "Stopping existing containers..."
docker-compose down -v

# Copy environment file
echo "Setting up environment file..."
if [ ! -f .env ]; then
    cp docker/.env.docker .env
    echo ".env file created from docker/.env.docker"
else
    echo ".env file already exists, skipping..."
fi

# Build and start containers
echo "Building Docker containers..."
docker-compose build --no-cache

echo "Starting Docker containers..."
docker-compose up -d

# Wait for database to be ready
echo "Waiting for database to be ready..."
sleep 10

# Install dependencies
echo "Installing Composer dependencies..."
docker-compose exec -T app composer install

# Generate application key
echo "Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo "Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Seed database
echo "Seeding database..."
docker-compose exec -T app php artisan db:seed --force

# Seed translations for testing
echo "Seeding 100k translations for performance testing..."
docker-compose exec -T app php artisan translations:seed 100000

# Clear caches
echo "Clearing caches..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan route:clear

# Set permissions
echo "Setting permissions..."
docker-compose exec -T app chown -R www-data:www-data /var/www/storage
docker-compose exec -T app chown -R www-data:www-data /var/www/bootstrap/cache

echo ""
echo "========================================="
echo "Setup Complete!"
echo "========================================="
echo ""
echo "API is now available at: http://localhost:8000"
echo ""
echo "Test the API:"
echo "  curl http://localhost:8000/api/translations"
echo ""
echo "View logs:"
echo "  docker-compose logs -f app"
echo ""
echo "Access container:"
echo "  docker-compose exec app bash"
echo ""
echo "Stop containers:"
echo "  docker-compose down"
echo ""
