# JAI E-Kanban

JAI E-Kanban modernizes the kanban workflow for PT JAI by digitizing supplier orders, stock replenishment, and access provisioning. The system features database-driven menus, group-based permissions, and modal-first CRUD flows that keep the UX fast and consistent with the legacy web properties.

## ✨ Key Features

- User, user-group, and menu management with role-based access controls
- Dynamic sidebar navigation sourced from menu definitions
- AJAX/DataTables CRUD with Select2-enhanced forms and SweetAlert confirmations
- Permission middleware that enforces per-action capabilities (create/read/update/delete)
- Blue-themed AdminLTE interface aligned with existing JAI styling

## 🧰 Technology Stack

| Layer | Tools |
| --- | --- |
| Backend | Laravel 12.39 (PHP 8.4) |
| Frontend | AdminLTE 3, Bootstrap 4, jQuery, Select2, SweetAlert2, Yajra DataTables |
| Database | MySQL 8.0 |
| Cache / Queue | Redis |
| Web Server | Apache 2.4 |
| DevOps | Docker & Docker Compose |

## 🚀 Quick Start (Docker)

```bash
git clone https://github.com/hasanupin/jai_e_kanban.git
cd jai_e_kanban

cp .env.example .env
docker-compose up -d --build

docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
docker-compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

Open **http://localhost:8001** to access the app.

### Default Ports

- Application: `8001`
- MySQL: `3308`
- Redis: `6381`

### Useful Commands

```bash
docker-compose logs -f
docker-compose exec app bash
docker-compose exec app php artisan migrate:fresh --seed
docker-compose down
```

## 💻 Running Without Docker

If you maintain a local PHP environment:

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure DB credentials in .env, then
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8001
```

Ensure MySQL 8.0 and Redis are running locally and match your `.env` settings.

## 📚 Additional Documentation

- **[README-Docker.md](README-Docker.md)** – Detailed container workflow & troubleshooting
- **[COMMANDS.md](COMMANDS.md)** – Frequently used artisan/docker commands
- **[PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)** – Architectural highlights & module breakdown
- **[COMPARISON.md](COMPARISON.md)** – Feature parity notes vs. jai-sampling-qa-apps

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
