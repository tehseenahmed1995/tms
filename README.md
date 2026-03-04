# Translation Management Service

A high-performance API-driven translation management service built with Laravel 12, designed for scalability and optimized for sub-200ms response times.

## Features

- Multi-locale translation storage (en, fr, es, etc.)
- Tag-based organization (mobile, desktop, web)
- Full CRUD operations with search capabilities
- Optimized JSON export endpoint for frontend applications
- Token-based authentication (Laravel Sanctum)
- User registration and authentication
- Caching with automatic invalidation
- Database query optimization with eager loading
- PSR-12 compliant code
- SOLID design principles
- Comprehensive test coverage (193 tests, 9,340+ assertions)

## Performance Benchmarks

- Standard endpoints: < 200ms response time
- JSON export endpoint: < 500ms (even with 100k+ records)
- Optimized database queries with proper indexing
- Redis caching for frequently accessed data

## Requirements

- PHP 8.2+
- Composer
- PostgreSQL/MySQL
- Redis (optional, for caching)
- Docker & Docker Compose (for containerized setup)

## Quick Start

### Option 1: Docker (Recommended)

```bash
# Windows (PowerShell)
powershell -ExecutionPolicy Bypass -File .\docker\setup.ps1

# Linux/Mac
chmod +x docker/setup.sh
./docker/setup.sh
```

The API will be available at **http://localhost:8000**

For detailed Docker documentation, troubleshooting, and advanced configuration, see **[docker/README.md](docker/README.md)**

### Option 2: Local Development

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=tms
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

# Run migrations and seed data
php artisan migrate
php artisan db:seed

# Seed 100k translations for performance testing
php artisan translations:seed 100000

# Start server
php artisan serve
```

The API will be available at **http://localhost:8000**

## API Documentation

### Quick API Reference

#### Authentication

**Register:**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Login:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

**Logout:**
```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Translations

**List All (Public):**
```bash
curl http://localhost:8000/api/translations
```

**Create (Authenticated):**
```bash
curl -X POST http://localhost:8000/api/translations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "welcome.message",
    "locale": "en",
    "content": "Welcome!",
    "tags": ["web"]
  }'
```

**Search (Public):**
```bash
curl "http://localhost:8000/api/translations/search?locale=en&tags[]=web"
```

**Export for Frontend (Public):**
```bash
curl "http://localhost:8000/api/translations/export?locale=en&nested=true"
```

**List Tags (Public):**
```bash
curl http://localhost:8000/api/tags
```

### OpenAPI/Swagger Documentation

Complete API documentation is available in `openapi.yaml` (OpenAPI 3.0.3 format).

**View Interactive Documentation:**

1. **Swagger UI Docker** (No CORS issues):
   ```bash
   docker run -p 8080:8080 -e SWAGGER_JSON=/openapi.yaml \
     -v ${PWD}/openapi.yaml:/openapi.yaml swaggerapi/swagger-ui
   ```
   Visit: http://localhost:8080

2. **Swagger Editor Online**: 
   - Go to https://editor.swagger.io/
   - Paste `openapi.yaml` content

3. **Postman/Insomnia**: 
   - Import `openapi.yaml` file

**CORS Note:** If you encounter CORS errors in Swagger UI, use the Docker method above or import into Postman.

## Key Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | No | Register new user |
| POST | `/api/auth/login` | No | Login and get token |
| POST | `/api/auth/logout` | Yes | Logout and revoke token |
| GET | `/api/translations` | No | List translations (paginated) |
| POST | `/api/translations` | Yes | Create translation |
| GET | `/api/translations/{id}` | No | Get single translation |
| PUT | `/api/translations/{id}` | Yes | Update translation |
| DELETE | `/api/translations/{id}` | Yes | Delete translation |
| GET | `/api/translations/search` | No | Search translations |
| GET | `/api/translations/export` | No | Export for frontend (JSON) |
| GET | `/api/tags` | No | List all tags |

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with Docker
docker-compose exec app php artisan test
```

**Test Coverage:** 193 tests with 9,340+ assertions
- Unit Tests: Services, DTOs, Resources
- Feature Tests: API endpoints, authentication, validation
- Property-Based Tests: Authentication enforcement, pagination, token revocation

## Performance Testing

```bash
# Seed 100k translations
php artisan translations:seed 100000

# Test export performance (should be < 500ms)
curl -w "\nTime: %{time_total}s\n" \
  "http://localhost:8000/api/translations/export?locale=en"
```

## Architecture & Design

### Design Patterns

- **Repository Pattern**: Data access abstraction
- **Service Layer**: Business logic separation
- **DTOs**: Type-safe data structures
- **Resource Transformers**: Consistent API responses

### Performance Optimizations

1. **Database Indexing**:
   - Composite index on (locale, key)
   - Index on locale for export queries

2. **Caching**:
   - Redis cache for export endpoint
   - ETag support for conditional requests
   - Automatic cache invalidation

3. **Query Optimization**:
   - Eager loading to prevent N+1 queries
   - Efficient pagination

### Security

- Token-based authentication (Laravel Sanctum)
- Input validation via Form Requests
- SQL injection prevention (Eloquent ORM)
- CORS enabled for API access

### Code Quality

- PSR-12 compliant (enforced via Laravel Pint)
- SOLID principles throughout
- PHP 8.2 type hints
- No external CRUD libraries

## Database Schema

**translations**
- Columns: id, key, locale, content, created_at, updated_at
- Indexes: (locale, key), locale
- Unique: (key, locale)

**tags**
- Columns: id, name, created_at, updated_at
- Unique: name

**translation_tag** (pivot)
- Columns: translation_id, tag_id
- Primary key: (translation_id, tag_id)

**users**
- Columns: id, name, email, password, created_at, updated_at
- Unique: email

## Common Commands

```bash
# Seed translations
php artisan translations:seed 100000

# Clear cache
php artisan cache:clear

# Format code (PSR-12)
./vendor/bin/pint

# Run migrations
php artisan migrate

# Fresh database
php artisan migrate:fresh --seed
```

## Docker Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f app

# Access container
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan [command]
```

For complete Docker documentation, see **[docker/README.md](docker/README.md)**

## Troubleshooting

### CORS Errors in Swagger
- Use Swagger UI Docker (see API Documentation section)
- Or import `openapi.yaml` into Postman/Insomnia

### Database Connection Failed
```bash
# Check database is running
docker-compose ps

# Restart database
docker-compose restart postgres
```

### Permission Errors
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

### 500 Internal Server Error
```bash
# Check logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan config:clear
php artisan cache:clear
```

## Project Structure

```
tms/
├── app/                      # Application code
│   ├── Console/Commands/     # Artisan commands
│   ├── Http/Controllers/Api/ # API controllers
│   ├── Models/               # Eloquent models
│   ├── Repositories/         # Data access layer
│   └── Services/             # Business logic
├── docker/                   # Docker configuration
│   ├── README.md            # Docker documentation
│   ├── setup.ps1            # Windows setup
│   └── setup.sh             # Linux/Mac setup
├── tests/                    # Test suite (193 tests)
├── docker-compose.yml        # Docker services
├── openapi.yaml              # API documentation
└── README.md                 # This file
```

## Contributing

1. Follow PSR-12 coding standards
2. Write tests for new features
3. Update API documentation (openapi.yaml)
4. Run tests before committing: `php artisan test`

## License

MIT License
