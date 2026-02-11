# JAI E-Kanban Application

JAI E-Kanban is a web-based kanban management system that digitizes supplier ordering, kanban card tracking, and access-control workflows for PT JAI. The application offers user, group, and menu administration with granular permissions, AJAX-driven forms, and responsive modals on top of the AdminLTE UI kit.

## Technology Stack

- **Language & Runtime:** PHP 8.4
- **Framework:** Laravel 12.39
- **Front-end UI:** AdminLTE 3 (Bootstrap 4), jQuery, Select2, SweetAlert2, Yajra DataTables
- **Database:** MySQL 8.0
- **Caching & Session:** Redis
- **Web Server:** Apache 2.4
- **Runtime Environment:** Docker & Docker Compose
- **Queue / Worker Ready:** Laravel queues (configurable)

## Local Development Setup (Docker)

### 1. Prerequisites

- Docker Desktop / Docker Engine 24+
- Docker Compose V2

### 2. Clone the Repository

```bash
git clone https://github.com/hasanupin/jai_e_kanban.git
cd jai_e_kanban
```

### 3. Environment Configuration

```bash
cp .env.example .env
```

The template already includes sensible defaults:

| Key | Value |
| --- | --- |
| DB_DATABASE | jai_e_kanban |
| DB_HOST | db |
| DB_USERNAME | laravel_user |
| DB_PASSWORD | laravel_password |

### 4. Build and Start Containers

```bash
docker-compose up -d --build
```

Services launched:

| Service | Description | Host Port |
| --- | --- | --- |
| app | Laravel + Apache | 8001 |
| db | MySQL 8.0 | 3308 |
| redis | Redis cache | 6381 |

### 5. Install Dependencies & Generate Key

```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
```

### 6. Run Migrations & Seeders (Optional)

```bash
docker-compose exec app php artisan migrate --seed
```

### 7. Set Writable Permissions

```bash
docker-compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

### 8. Access the Application

Visit `http://localhost:8001` in your browser.

### Useful Docker Commands

```bash
docker-compose logs -f            # Tail service logs
docker-compose exec app bash      # Shell into the PHP container
docker-compose exec app php artisan migrate:fresh --seed
docker-compose down               # Stop and remove containers
```

### Database & Cache

- MySQL DSN: `mysql://laravel_user:laravel_password@127.0.0.1:3308/jai_e_kanban`
- Redis: `redis://127.0.0.1:6381`

## Running Without Docker

> If you prefer a local PHP environment, ensure PHP 8.4, Composer, MySQL 8, and Redis are installed.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8001
```

MySQL credentials should match your local setup; update `.env` accordingly.

## Project Highlights

- Dynamic, database-driven sidebar and permissions tied to user groups
- User, group, and menu CRUD refactored into dedicated services and form requests
- Consistent JSON responses with custom helper for AJAX flows
- Modal-powered CRUD forms, DataTables integration, and Select2 enhanced selects
- Middleware-enforced menu access with per-action permission flags
- Blue AdminLTE theme derived from legacy JAI styling for UI consistency

## Troubleshooting

- Rebuild containers:
	```bash
	docker-compose down
	docker-compose up -d --build
	```
- Reset database:
	```bash
	docker-compose exec app php artisan migrate:fresh --seed
	```
- Permission issues:
	```bash
	docker-compose exec app chown -R www-data:www-data /var/www/html
	docker-compose exec app chmod -R 775 /var/www/html/storage
	```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

