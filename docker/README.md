# Docker Setup Guide

Complete guide for running the Translation Management Service with Docker.

## Quick Start

### Windows (PowerShell)
```powershell
# From project root
powershell -ExecutionPolicy Bypass -File .\docker\setup.ps1

# Or from docker folder
cd docker
powershell -ExecutionPolicy Bypass -File .\setup.ps1
```

### Linux/Mac
```bash
# From project root
chmod +x docker/setup.sh
./docker/setup.sh

# Or from docker folder
cd docker
chmod +x setup.sh
./setup.sh
```

### Manual Setup
```bash
# 1. Copy environment file
cp docker/.env.docker .env

# 2. Build and start containers
docker-compose up -d --build

# 3. Install dependencies
docker-compose exec app composer install

# 4. Generate application key
docker-compose exec app php artisan key:generate

# 5. Run migrations
docker-compose exec app php artisan migrate --force

# 6. Seed database
docker-compose exec app php artisan db:seed --force
docker-compose exec app php artisan translations:seed 100000

# 7. Clear caches
docker-compose exec app php artisan config:clear
```

## Services

The Docker setup includes:

- **app** - PHP 8.4-FPM with Laravel application
- **nginx** - Web server (port 8000)
- **postgres** - PostgreSQL 16 database (port 5433)
- **redis** - Redis cache (port 6379)

## Accessing the Application

- **API**: http://localhost:8000/api
- **Health Check**: http://localhost:8000/up

## Common Commands

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f postgres
```

### Access Container Shell
```bash
# App container
docker-compose exec app bash

# Database container
docker-compose exec postgres psql -U tms_user -d tms
```

### Run Artisan Commands
```bash
# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed

# Clear cache
docker-compose exec app php artisan cache:clear

# Run tests
docker-compose exec app php artisan test
```

### Restart Services
```bash
# Restart all services
docker-compose restart

# Restart specific service
docker-compose restart app
docker-compose restart nginx
```

### Stop and Remove
```bash
# Stop containers
docker-compose stop

# Stop and remove containers
docker-compose down

# Stop, remove containers and volumes (WARNING: deletes data)
docker-compose down -v
```

## Troubleshooting

### Issue: Containers won't start

**Check Docker is running:**
```bash
docker --version
docker-compose --version
```

**Check for port conflicts:**
```bash
# Windows
netstat -ano | findstr :8000
netstat -ano | findstr :5433
netstat -ano | findstr :6379

# Linux/Mac
lsof -i :8000
lsof -i :5433
lsof -i :6379
```

**Solution:** Stop services using those ports or change ports in `docker-compose.yml`

### Issue: Database connection failed

**Check database is ready:**
```bash
docker-compose exec postgres pg_isready -U tms_user -d tms
```

**Check database logs:**
```bash
docker-compose logs postgres
```

**Recreate database:**
```bash
docker-compose down -v
docker-compose up -d
# Wait 10 seconds
docker-compose exec app php artisan migrate --force
```

### Issue: Permission denied errors

**Fix permissions:**
```bash
docker-compose exec app chown -R www-data:www-data /var/www/storage
docker-compose exec app chown -R www-data:www-data /var/www/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/storage
docker-compose exec app chmod -R 775 /var/www/bootstrap/cache
```

### Issue: Composer dependencies not installed

**Install dependencies:**
```bash
docker-compose exec app composer install
```

**Clear Composer cache:**
```bash
docker-compose exec app composer clear-cache
docker-compose exec app composer install
```

### Issue: Application key not set

**Generate key:**
```bash
docker-compose exec app php artisan key:generate
```

### Issue: 500 Internal Server Error

**Check app logs:**
```bash
docker-compose logs -f app
docker-compose logs -f nginx
```

**Check Laravel logs:**
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

**Clear all caches:**
```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Issue: CORS errors in Swagger

The Docker setup includes CORS configuration. If you still see CORS errors:

1. **Verify CORS middleware is enabled** (already done in `bootstrap/app.php`)
2. **Use Swagger UI Docker** (no CORS issues):
   ```bash
   docker run -p 8080:8080 -e SWAGGER_JSON=/openapi.yaml -v ${PWD}/openapi.yaml:/openapi.yaml swaggerapi/swagger-ui
   ```
3. **Update OpenAPI server URL** to `http://localhost:8000/api`

## Testing the API

### Quick Test
```bash
# Test public endpoint
curl http://localhost:8000/api/translations

# Test with PowerShell
Invoke-WebRequest -Uri http://localhost:8000/api/translations -UseBasicParsing
```

### Run Test Suite
```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test
docker-compose exec app php artisan test --filter=RegisterControllerTest
```

### Performance Testing
```bash
# Seed 100k translations
docker-compose exec app php artisan translations:seed 100000

# Test export performance
curl -w "@curl-format.txt" http://localhost:8000/api/translations/export?locale=en
```

## Database Management

### Access Database
```bash
docker-compose exec postgres psql -U tms_user -d tms
```

### Common SQL Commands
```sql
-- List tables
\dt

-- View translations count
SELECT COUNT(*) FROM translations;

-- View users
SELECT * FROM users;

-- Exit
\q
```

### Backup Database
```bash
docker-compose exec postgres pg_dump -U tms_user tms > backup.sql
```

### Restore Database
```bash
cat backup.sql | docker-compose exec -T postgres psql -U tms_user -d tms
```

## Redis Management

### Access Redis CLI
```bash
docker-compose exec redis redis-cli
```

### Common Redis Commands
```bash
# Check connection
PING

# View all keys
KEYS *

# Clear all cache
FLUSHALL

# Exit
exit
```

## Production Deployment

For production, update the following:

1. **Environment Variables** (`.env`):
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   ```

2. **CORS Configuration** (`config/cors.php`):
   ```php
   'allowed_origins' => [
       'https://yourdomain.com',
       'https://app.yourdomain.com',
   ],
   ```

3. **Database Credentials**: Use strong passwords

4. **SSL/TLS**: Configure HTTPS in nginx

5. **Optimize Application**:
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   docker-compose exec app composer install --optimize-autoloader --no-dev
   ```

## Monitoring

### Check Container Status
```bash
docker-compose ps
```

### Check Resource Usage
```bash
docker stats
```

### Health Checks
```bash
# Application health
curl http://localhost:8000/up

# Database health
docker-compose exec postgres pg_isready -U tms_user -d tms

# Redis health
docker-compose exec redis redis-cli ping
```

## Updating the Application

```bash
# Pull latest code
git pull

# Rebuild containers
docker-compose down
docker-compose up -d --build

# Update dependencies
docker-compose exec app composer install

# Run migrations
docker-compose exec app php artisan migrate --force

# Clear caches
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
```

## Support

For issues or questions:
1. Check the logs: `docker-compose logs -f`
2. Review this guide
3. Check `SWAGGER_TESTING_GUIDE.md` for API testing
4. Review `README.md` for application details
