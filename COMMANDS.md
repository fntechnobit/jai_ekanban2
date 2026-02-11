# JAI E-Kanban - Quick Command Reference

## Initial Setup

```bash
# Start containers for the first time
docker-compose up -d --build

# Install dependencies
docker-compose exec app composer install

# Run migrations
docker-compose exec app php artisan migrate

# Set permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

## Daily Development

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f app

# Access container shell (using docker-compose)
docker-compose exec app bash

# Access container shell (using docker exec)
docker exec -it jai_e_kanban_app bash
```

## Artisan Commands

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Rollback migrations
docker-compose exec app php artisan migrate:rollback

# Seed database
docker-compose exec app php artisan db:seed

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Create files
docker-compose exec app php artisan make:model ModelName -m
docker-compose exec app php artisan make:controller ControllerName
docker-compose exec app php artisan make:migration create_table_name
docker-compose exec app php artisan make:seeder SeederName
```

## Composer Commands

```bash
# Install package
docker-compose exec app composer require package/name

# Update dependencies
docker-compose exec app composer update

# Dump autoload
docker-compose exec app composer dump-autoload
```

## Database Access

```bash
# Access MySQL shell (inside Docker)
docker-compose exec db mysql -u laravel_user -plaravel_password jai_e_kanban

# Access MySQL from host machine
mysql -h 127.0.0.1 -P 3308 -u laravel_user -plaravel_password jai_e_kanban

# Export database
docker-compose exec db mysqldump -u laravel_user -plaravel_password jai_e_kanban > backup.sql

# Import database
docker-compose exec -T db mysql -u laravel_user -plaravel_password jai_e_kanban < backup.sql
```

## Useful Checks

```bash
# Check PHP version
docker-compose exec app php -v

# Check Laravel version
docker-compose exec app php artisan --version

# List routes
docker-compose exec app php artisan route:list

# Check database connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

## Troubleshooting

```bash
# Rebuild containers from scratch
docker-compose down -v
docker-compose up -d --build

# Fix permissions
docker-compose exec app chown -R www-data:www-data /var/www/html
docker-compose exec app chmod -R 775 /var/www/html/storage

# View container status
docker-compose ps

# View resource usage
docker stats
```

## Application Access

- **Web Application:** http://localhost:8001
- **MySQL:** localhost:3308
- **Redis:** localhost:6381

## Database Credentials

- **Host:** db (inside Docker) or localhost:3308 (external)
- **Database:** jai_e_kanban
- **Username:** laravel_user
- **Password:** laravel_password
- **Root Password:** root_password
