# Deployment Guide - JAI E-Kanban System

**Deployment Date:** January 28, 2026
**Package Version:** 1.0
**Project:** JAI E-Kanban System

## Table of Contents
1. [Package Contents](#package-contents)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Backup Instructions](#backup-instructions)
4. [Deployment Steps](#deployment-steps)
5. [Post-Deployment Steps](#post-deployment-steps)
6. [Files to Delete](#files-to-delete)
7. [Rollback Instructions](#rollback-instructions)
8. [Troubleshooting](#troubleshooting)

---

## Package Contents

This deployment package contains the following changes:

### New Features
- **Defect Management Module**
  - New DefectController for handling defect logs
  - DefectService for business logic
  - Defect log models for Circuit and Shikake
  - Defect recording views (cutting, shikake, history)

- **Kanban Balance System**
  - KanbanBalanceCircuit and KanbanBalanceShikake models
  - KanbanGeneratorService for kanban generation logic
  - Enhanced kanban printing with separate preview and print views

- **Template Generation System**
  - CircuitTemplateConfig for circuit templates
  - GenerateTemplates command (replaces GenerateShikakeTemplates)
  - GenerateSampleDataTemplate command for sample data

### Database Migrations
- 13 new migration files (see details below)
- Schema changes for master tables
- New tables: kanban_balance_circuit, kanban_balance_shikake, defect_log_circuit, defect_log_shikake

### Enhanced Features
- Updated all Shikake import classes
- Enhanced print ticket views (separate preview and print)
- Updated master data controllers and views
- Modified schedule verification service
- Updated template Excel files

### Configuration Changes
- Session configuration updates
- Environment example updates

---

## Pre-Deployment Checklist

Before deploying, ensure:

- [ ] **Backup completed** (database and files)
- [ ] **Server access** (SSH/FTP credentials ready)
- [ ] **PHP version** compatible (Laravel requirements)
- [ ] **Database access** for running migrations
- [ ] **Application in maintenance mode**
- [ ] **No active users** on the system
- [ ] **Sufficient disk space** available
- [ ] **Composer dependencies** can be updated

---

## Backup Instructions

### 1. Backup Database
```bash
# On the server, run:
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# Or use your database management tool to export the database
```

### 2. Backup Application Files
```bash
# Create backup of current application
cd /path/to/application
tar -czf backup_$(date +%Y%m%d_%H%M%S).tar.gz .

# Or backup specific directories
tar -czf backup_app_$(date +%Y%m%d_%H%M%S).tar.gz app/ resources/ database/ config/ routes/ public/
```

### 3. Store Backups Safely
```bash
# Move backups to a safe location
mkdir -p /path/to/backups
mv backup_*.* /path/to/backups/
```

---

## Deployment Steps

### Step 1: Put Application in Maintenance Mode
```bash
php artisan down --message="System update in progress" --retry=60
```

### Step 2: Extract Deployment Package
```bash
# Extract the deployment_package.zip to a temporary location
unzip deployment_package.zip -d /tmp/deployment_package
```

### Step 3: Copy Files to Application Directory
```bash
cd /tmp/deployment_package

# Copy all files maintaining directory structure
# Replace /path/to/your/application with your actual application path

# Copy application files
cp -r app/* /path/to/your/application/app/
cp -r resources/* /path/to/your/application/resources/
cp -r database/* /path/to/your/application/database/
cp -r config/* /path/to/your/application/config/
cp -r public/* /path/to/your/application/public/
cp -r routes/* /path/to/your/application/routes/
cp -r storage/* /path/to/your/application/storage/
cp -r docs/* /path/to/your/application/docs/

# Copy configuration files
cp .env.example /path/to/your/application/.env.example
cp composer.lock /path/to/your/application/composer.lock
```

### Step 4: Delete Obsolete Files
See the [Files to Delete](#files-to-delete) section below and remove these files from your server:

```bash
cd /path/to/your/application

# Delete old files
rm -f app/Console/Commands/GenerateShikakeTemplates.php
rm -f resources/views/schedule/ekanban_shikake/print_ticket_bonder.blade.php
rm -f resources/views/schedule/ekanban_shikake/print_ticket_dbl_crimp.blade.php
rm -f resources/views/schedule/ekanban_shikake/print_ticket_joint.blade.php
rm -f resources/views/schedule/ekanban_shikake/print_ticket_shield.blade.php
```

### Step 5: Set Correct Permissions
```bash
cd /path/to/your/application

# Set ownership (adjust user:group as needed)
chown -R www-data:www-data .

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Set storage and cache permissions
chmod -R 775 storage bootstrap/cache
```

### Step 6: Install/Update Composer Dependencies
```bash
cd /path/to/your/application

# Update composer dependencies
composer install --no-dev --optimize-autoloader
```

### Step 7: Clear Application Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 8: Run Database Migrations
```bash
# Run migrations in order
php artisan migrate

# If you encounter errors, you can run migrations one by one:
# php artisan migrate --path=/database/migrations/2026_01_12_210519_remove_released_date_from_child_process_tables.php
```

### Step 9: Optimize Application
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Step 10: Bring Application Online
```bash
php artisan up
```

---

## Post-Deployment Steps

### 1. Verify Application Functionality
- [ ] Access the application homepage
- [ ] Test login functionality
- [ ] Verify master data pages load correctly
- [ ] Test new Defect Management module
- [ ] Verify Kanban generation works
- [ ] Test print preview and print functionality
- [ ] Check import functionality with new templates

### 2. Test Critical Features
- [ ] Master Circuit CRUD operations
- [ ] Master Shikake CRUD operations
- [ ] E-Kanban Circuit generation
- [ ] E-Kanban Shikake generation
- [ ] Defect logging (Cutting and Shikake)
- [ ] Template imports (all types)
- [ ] Print tickets (all types)

### 3. Check Logs for Errors
```bash
tail -f storage/logs/laravel.log
```

### 4. Monitor System Performance
- Check database query performance
- Monitor server resources (CPU, memory, disk)
- Review application response times

---

## Files to Delete

The following files are obsolete and should be deleted from your server:

### Console Commands
```
app/Console/Commands/GenerateShikakeTemplates.php
```

### Blade Views
```
resources/views/schedule/ekanban_shikake/print_ticket_bonder.blade.php
resources/views/schedule/ekanban_shikake/print_ticket_dbl_crimp.blade.php
resources/views/schedule/ekanban_shikake/print_ticket_joint.blade.php
resources/views/schedule/ekanban_shikake/print_ticket_shield.blade.php
```

**Note:** The file `print_ticket_twist.blade.php` was renamed to `print_ticket_twist_preview.blade.php`. Make sure the old file is removed if it still exists.

---

## Rollback Instructions

If you encounter critical issues during deployment:

### 1. Put Application in Maintenance Mode
```bash
php artisan down
```

### 2. Restore Database Backup
```bash
mysql -u [username] -p [database_name] < /path/to/backups/backup_YYYYMMDD_HHMMSS.sql
```

### 3. Restore Application Files
```bash
cd /path/to/application
rm -rf *  # BE CAREFUL! Make sure you have backups
tar -xzf /path/to/backups/backup_YYYYMMDD_HHMMSS.tar.gz
```

### 4. Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 5. Bring Application Online
```bash
php artisan up
```

---

## Troubleshooting

### Issue: Migration Errors

**Problem:** Migration fails with foreign key constraint errors
**Solution:**
```bash
# Check database state
php artisan migrate:status

# Rollback last batch if needed
php artisan migrate:rollback

# Run migrations again
php artisan migrate
```

### Issue: Permission Denied Errors

**Problem:** Application shows permission denied errors
**Solution:**
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Issue: Class Not Found Errors

**Problem:** "Class not found" errors appear
**Solution:**
```bash
# Regenerate autoload files
composer dump-autoload

# Clear and rebuild cache
php artisan config:clear
php artisan cache:clear
php artisan optimize
```

### Issue: Views Not Updating

**Problem:** Old views still appear after deployment
**Solution:**
```bash
# Clear view cache
php artisan view:clear

# Clear all caches
php artisan cache:clear
php artisan config:clear
```

### Issue: Routes Not Working

**Problem:** New routes return 404 errors
**Solution:**
```bash
# Clear route cache
php artisan route:clear

# Rebuild route cache
php artisan route:cache
```

---

## Database Migrations Overview

This deployment includes the following migrations (in order):

1. **2026_01_12_210519** - Remove released_date from child process tables
2. **2026_01_13_000001** - Alter master_shikake_dbl_crimp table
3. **2026_01_18_025451** - Add carline to master_shikake and master_circuit tables
4. **2026_01_18_025949** - Remove issue_barcode, kanban, released_note from master tables
5. **2026_01_20_000001** - Rename bonder_no to address_no in master_shikake_shield table
6. **2026_01_20_000002** - Create kanban_balance table (deprecated)
7. **2026_01_20_000003** - Add kanban fields to assy_schedule_circuit table
8. **2026_01_20_000004** - Add kanban fields to assy_schedule_shikake table
9. **2026_01_20_000005** - Create defect_log table (deprecated)
10. **2026_01_24_214105** - Add released_note and cleanup columns
11. **2026_01_25_000001** - Drop unique circuit_group constraint
12. **2026_01_27_000001** - Separate kanban_balance tables (circuit and shikake)
13. **2026_01_27_000002** - Separate defect_log tables (circuit and shikake)
14. **2026_01_28_000001** - Add defect menu to system

---

## Summary of Changes

### Files Modified: 53
### Files Added: 40
### Files Deleted: 5
### Total Files in Package: 93

### Key Changes:
- **Defect Management System**: Complete module for tracking and managing defects
- **Kanban Balance System**: Enhanced kanban generation and tracking
- **Template Management**: Improved template configuration and generation
- **Print System**: Separate preview and print views for better UX
- **Database Schema**: Significant improvements to data structure
- **Import System**: Enhanced data import capabilities

---

## Support

For issues or questions during deployment:
1. Check the Troubleshooting section above
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check web server error logs
4. Contact the development team

---

## Deployment Checklist

Print this checklist and mark items as completed:

- [ ] Backup database completed
- [ ] Backup application files completed
- [ ] Application put in maintenance mode
- [ ] Deployment package extracted
- [ ] Files copied to application directory
- [ ] Obsolete files deleted
- [ ] File permissions set correctly
- [ ] Composer dependencies updated
- [ ] Application caches cleared
- [ ] Database migrations executed successfully
- [ ] Application optimized
- [ ] Application brought online
- [ ] Functionality verification completed
- [ ] Critical features tested
- [ ] Error logs checked
- [ ] System performance monitored
- [ ] Deployment completed successfully

---

**End of Deployment Guide**

Generated on: January 28, 2026
