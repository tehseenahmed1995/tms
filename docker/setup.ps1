# Translation Management Service - Docker Setup Script for Windows

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Translation Management Service - Docker Setup" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Change to project root
Set-Location ..

# Stop and remove existing containers
Write-Host "Stopping existing containers..." -ForegroundColor Yellow
docker-compose down -v

# Copy environment file
Write-Host "Setting up environment file..." -ForegroundColor Yellow
if (-not (Test-Path .env)) {
    Copy-Item docker/.env.docker .env
    Write-Host ".env file created from docker/.env.docker" -ForegroundColor Green
} else {
    Write-Host ".env file already exists, skipping..." -ForegroundColor Yellow
}

# Build and start containers
Write-Host "Building Docker containers..." -ForegroundColor Yellow
docker-compose build --no-cache

Write-Host "Starting Docker containers..." -ForegroundColor Yellow
docker-compose up -d

# Wait for database to be ready
Write-Host "Waiting for database to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Install dependencies
Write-Host "Installing Composer dependencies..." -ForegroundColor Yellow
docker-compose exec -T app composer install

# Generate application key
Write-Host "Generating application key..." -ForegroundColor Yellow
docker-compose exec -T app php artisan key:generate

# Run migrations
Write-Host "Running database migrations..." -ForegroundColor Yellow
docker-compose exec -T app php artisan migrate --force

# Seed database
Write-Host "Seeding database..." -ForegroundColor Yellow
docker-compose exec -T app php artisan db:seed --force

# Seed translations for testing
Write-Host "Seeding 100k translations for performance testing..." -ForegroundColor Yellow
docker-compose exec -T app php artisan translations:seed 100000

# Clear caches
Write-Host "Clearing caches..." -ForegroundColor Yellow
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan route:clear

# Set permissions
Write-Host "Setting permissions..." -ForegroundColor Yellow
docker-compose exec -T app chown -R www-data:www-data /var/www/storage
docker-compose exec -T app chown -R www-data:www-data /var/www/bootstrap/cache

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Setup Complete!" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "API is now available at: http://localhost:8000" -ForegroundColor Green
Write-Host ""
Write-Host "Test the API:" -ForegroundColor Yellow
Write-Host "  Invoke-WebRequest -Uri http://localhost:8000/api/translations -UseBasicParsing" -ForegroundColor White
Write-Host ""
Write-Host "View logs:" -ForegroundColor Yellow
Write-Host "  docker-compose logs -f app" -ForegroundColor White
Write-Host ""
Write-Host "Access container:" -ForegroundColor Yellow
Write-Host "  docker-compose exec app bash" -ForegroundColor White
Write-Host ""
Write-Host "Stop containers:" -ForegroundColor Yellow
Write-Host "  docker-compose down" -ForegroundColor White
Write-Host ""
