# JAI E-Kanban Application

E-Kanban management system built with Laravel 12 and PHP 8.4.

## Technology Stack

- **Framework:** Laravel 12
- **PHP Version:** 8.4
- **Database:** MySQL 8.0
- **Cache/Session:** Redis
- **Web Server:** Apache 2.4
- **Containerization:** Docker & Docker Compose

## Prerequisites

- Docker
- Docker Compose

## Installation & Setup

### 1. Clone and Setup

```bash
cd /path/to/jai_e_kanban
```

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

The `.env` file is pre-configured with the following database settings:
- Database: `jai_e_kanban`
- Host: `db` (Docker service)
- Port: `3306`
- Username: `laravel_user`
- Password: `laravel_password`

### 3. Build and Start Docker Containers

```bash
docker-compose up -d --build
```

This will start three services:
- **app** (Laravel application with PHP 8.4) - Port 8001
- **db** (MySQL 8.0) - Port 3307 (mapped from 3306)
- **redis** (Redis cache) - Port 6380 (mapped from 6379)

### 4. Install Dependencies

```bash
docker-compose exec app composer install
```

### 5. Generate Application Key

```bash
docker-compose exec app php artisan key:generate
```

### 6. Run Database Migrations

```bash
docker-compose exec app php artisan migrate
```

### 7. Set Permissions

```bash
docker-compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

## Access Application

Open your browser and navigate to:
```
http://localhost:8001
```

## Docker Commands

### Start containers
```bash
docker-compose up -d
```

### Stop containers
```bash
docker-compose down
```

### View logs
```bash
docker-compose logs -f
```

### Access Laravel container shell
```bash
# Using docker-compose
docker-compose exec app bash

# Using docker exec
docker exec -it jai_e_kanban_app bash
```

### Access MySQL database
```bash
docker-compose exec db mysql -u laravel_user -plaravel_password jai_e_kanban

# Or from host machine
mysql -h 127.0.0.1 -P 3308 -u laravel_user -plaravel_password jai_e_kanban
```

### Run Artisan commands
```bash
docker-compose exec app php artisan <command>
```

## Database Information

- **Database Name:** jai_e_kanban
- **MySQL Version:** 8.0
- **Internal Port:** 3306
- **External Port:** 3308
- **Root Password:** root_password
- **User:** laravel_user
- **Password:** laravel_password

## Redis Information

- **Version:** Alpine (latest)
- **Internal Port:** 6379
- **External Port:** 6381
- **Usage:** Session storage and cache

## Development

### Clear cache
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Create migration
```bash
docker-compose exec app php artisan make:migration create_table_name
```

### Create model
```bash
docker-compose exec app php artisan make:model ModelName -m
```

### Create controller
```bash
docker-compose exec app php artisan make:controller ControllerName
```

## Port Configuration

To avoid conflicts with other applications:
- Application: 8001 (instead of 8000)
- MySQL: 3308 (instead of 3306)
- Redis: 6381 (instead of 6379)

## Troubleshooting

### Permission Issues
```bash
docker-compose exec app chown -R www-data:www-data /var/www/html
docker-compose exec app chmod -R 775 /var/www/html/storage
```

### Rebuild containers
```bash
docker-compose down
docker-compose up -d --build
```

### Reset database
```bash
docker-compose exec app php artisan migrate:fresh
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
