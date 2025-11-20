# JAI E-Kanban Application

E-Kanban management system built with Laravel 12, PHP 8.4, and MySQL 8.0.

## ⚡ Quick Start

```bash
# Run the setup script
./setup.sh
```

Then open your browser to: **http://localhost:8001**

## 🚀 Technology Stack

- **Laravel** 12.39.0
- **PHP** 8.4
- **MySQL** 8.0
- **Redis** (Cache & Session)
- **Apache** 2.4
- **Docker** & Docker Compose

## 📋 Manual Setup

If you prefer manual setup:

```bash
# 1. Start containers
docker-compose up -d --build

# 2. Install dependencies
docker-compose exec app composer install

# 3. Run migrations
docker-compose exec app php artisan migrate

# 4. Set permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

## 🗄️ Database Configuration

- **Database:** jai_e_kanban
- **Host (External):** localhost:3308
- **Username:** laravel_user
- **Password:** laravel_password

## 📚 Documentation

- **[README-Docker.md](README-Docker.md)** - Comprehensive Docker setup guide
- **[COMMANDS.md](COMMANDS.md)** - Quick command reference
- **[PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)** - Project overview and details
- **[COMPARISON.md](COMPARISON.md)** - Comparison with jai-sampling-qa-apps

## 🔧 Common Commands

```bash
# View logs
docker-compose logs -f

# Access container shell (using docker-compose)
docker-compose exec app bash

# Access container shell (using docker exec)
docker exec -it jai_e_kanban_app bash

# Run Artisan commands
docker-compose exec app php artisan <command>

# Stop containers
docker-compose down
```

## 📍 Access Points

- **Application:** http://localhost:8001
- **MySQL:** localhost:3308
- **Redis:** localhost:6381

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
