# VPS Deployment Steps

## Step 1: SSH into your VPS server
```bash
ssh root@your-vps-ip
# or
ssh your-username@your-vps-ip
```

## Step 2: Navigate to your project directory
```bash
cd /home/taskbook-marks/htdocs/marks.taskbook.co.in
```

## Step 3: Pull latest changes from Git
```bash
# Make sure you're in the correct directory
git pull origin master
```

If you get an error about local changes, you can either:
- **Option A (Recommended):** Stash your local changes
  ```bash
  git stash
  git pull origin master
  git stash pop  # If you want to restore your local changes
  ```

- **Option B:** Discard local changes (if you don't need them)
  ```bash
  git reset --hard HEAD
  git pull origin master
  ```

## Step 4: Install/Update Dependencies

### Install PHP dependencies (Composer)
```bash
composer install --no-dev --optimize-autoloader
```

### Install Node.js dependencies (if package.json changed)
```bash
npm install
```

## Step 5: Run Database Migrations (if new migrations exist)
```bash
php artisan migrate --force
```

## Step 6: Clear and Cache Configuration
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache (for production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 7: Build Frontend Assets
```bash
# Build for production
npm run build
```

## Step 8: Set Proper Permissions (if needed)
```bash
# Set ownership (adjust user/group as needed)
chown -R taskbook-marks:taskbook-marks /home/taskbook-marks/htdocs/marks.taskbook.co.in

# Set directory permissions
find /home/taskbook-marks/htdocs/marks.taskbook.co.in -type d -exec chmod 755 {} \;

# Set file permissions
find /home/taskbook-marks/htdocs/marks.taskbook.co.in -type f -exec chmod 644 {} \;

# Make storage and bootstrap writable
chmod -R 775 storage bootstrap/cache
```

## Step 9: Restart Services (if needed)
```bash
# If using PHP-FPM
sudo systemctl restart php8.4-fpm
# or
sudo systemctl restart php-fpm

# If using Nginx
sudo systemctl restart nginx

# If using Apache
sudo systemctl restart apache2
```

## Step 10: Verify Deployment
1. Visit your website: `https://marks.taskbook.co.in`
2. Check if the new features are working:
   - Student profile print/PDF export
   - Class-wise Excel export
   - Grand total and average in student profiles
   - Results section

## Troubleshooting

### If you get "Class not found" errors:
```bash
composer dump-autoload
```

### If assets are not loading:
```bash
# Rebuild assets
npm run build

# Clear Laravel cache
php artisan cache:clear
php artisan view:clear
```

### If database errors occur:
```bash
# Check if migrations are needed
php artisan migrate:status

# Run migrations
php artisan migrate --force
```

### If permission errors:
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R taskbook-marks:taskbook-marks storage bootstrap/cache
```

## Quick Deployment Script (All-in-One)
```bash
cd /home/taskbook-marks/htdocs/marks.taskbook.co.in && \
git pull origin master && \
composer install --no-dev --optimize-autoloader && \
npm install && \
php artisan migrate --force && \
php artisan config:clear && \
php artisan cache:clear && \
php artisan route:clear && \
php artisan view:clear && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
npm run build && \
chmod -R 775 storage bootstrap/cache
```

## Notes:
- Replace `taskbook-marks` with your actual user/group if different
- The `--force` flag in migrate is needed for production
- Always backup your database before running migrations in production
- Check your `.env` file to ensure all settings are correct

