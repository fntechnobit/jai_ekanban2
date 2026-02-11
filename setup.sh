#!/bin/bash

# JAI E-Kanban Quick Start Script
# This script will set up and start the application

echo "========================================="
echo "JAI E-Kanban - Quick Start"
echo "========================================="
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running!"
    echo "Please start Docker Desktop and try again."
    exit 1
fi

echo "✅ Docker is running"
echo ""

# Step 1: Build and start containers
echo "📦 Building and starting Docker containers..."
docker-compose up -d --build

if [ $? -ne 0 ]; then
    echo "❌ Error: Failed to start containers"
    exit 1
fi

echo "✅ Containers started successfully"
echo ""

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 10

# Step 2: Install Composer dependencies
echo "📚 Installing Composer dependencies..."
docker-compose exec -T app composer install --no-interaction

if [ $? -ne 0 ]; then
    echo "⚠️  Warning: Failed to install Composer dependencies"
fi

echo ""

# Step 3: Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "⚠️  Warning: Failed to run migrations"
fi

echo ""

# Step 4: Set permissions
echo "🔒 Setting proper permissions..."
docker-compose exec -T app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker-compose exec -T app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Permissions set"
echo ""

# Display information
echo "========================================="
echo "✅ Setup Complete!"
echo "========================================="
echo ""
echo "📱 Application URL: http://localhost:8001"
echo ""
echo "🗄️  Database Information:"
echo "   - Host: localhost:3307"
echo "   - Database: jai_e_kanban"
echo "   - Username: laravel_user"
echo "   - Password: laravel_password"
echo ""
echo "📝 Useful Commands:"
echo "   - View logs: docker-compose logs -f"
echo "   - Stop: docker-compose down"
echo "   - Shell access: docker-compose exec app bash"
echo ""
echo "📚 Documentation:"
echo "   - README-Docker.md - Full setup guide"
echo "   - COMMANDS.md - Quick command reference"
echo "   - PROJECT_SUMMARY.md - Project overview"
echo ""
echo "Happy coding! 🚀"
echo ""
