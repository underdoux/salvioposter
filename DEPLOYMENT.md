# Deployment Guide

## System Requirements

- PHP 8.1 or higher
- Composer 2.0+
- Node.js 16+ and NPM
- SQLite 3
- Web Server (Apache/Nginx)
- SSL Certificate
- Cron job access

## Production Environment Setup

1. **Server Configuration**
   ```bash
   # Clone repository
   git clone https://github.com/yourusername/blogposter.git
   cd blogposter

   # Install dependencies
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build

   # Set permissions
   chmod -R 755 storage bootstrap/cache
   ```

2. **Environment Configuration**
   ```bash
   # Copy environment file
   cp .env.example .env

   # Generate application key
   php artisan key:generate

   # Configure environment variables
   DB_CONNECTION=sqlite
   DB_DATABASE=/absolute/path/to/database.sqlite

   GOOGLE_CLIENT_ID=your_production_client_id
   GOOGLE_CLIENT_SECRET=your_production_client_secret
   GOOGLE_REDIRECT_URI=https://your-domain.com/auth/google/callback
   ```

3. **Database Setup**
   ```bash
   # Create SQLite database
   touch database/database.sqlite

   # Run migrations
   php artisan migrate --force

   # Optimize database
   php artisan db:optimize
   ```

4. **Cron Jobs Setup**
   Add to crontab:
   ```bash
   * * * * * cd /path/to/blogposter && php artisan schedule:run >> /dev/null 2>&1
   ```

   This will run:
   - Post scheduling (every minute)
   - Analytics sync (hourly)
   - Notification cleanup (daily)

5. **Web Server Configuration**

   Apache (.htaccess):
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteBase /
       RewriteRule ^index\.php$ - [L]
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule . /index.php [L]
   </IfModule>
   ```

   Nginx (nginx.conf):
   ```nginx
   server {
       listen 80;
       server_name your-domain.com;
       root /path/to/blogposter/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";

       index index.php;

       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location = /favicon.ico { access_log off; log_not_found off; }
       location = /robots.txt  { access_log off; log_not_found off; }

       error_page 404 /index.php;

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

6. **SSL Configuration**
   ```bash
   # Install SSL certificate
   certbot --nginx -d your-domain.com

   # Verify SSL renewal
   certbot renew --dry-run
   ```

7. **Cache Configuration**
   ```bash
   # Clear all caches
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear

   # Cache for production
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

8. **Backup System**
   ```bash
   # Install backup package
   composer require spatie/laravel-backup

   # Configure backup
   php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

   # Add to crontab
   0 0 * * * cd /path/to/blogposter && php artisan backup:clean
   0 1 * * * cd /path/to/blogposter && php artisan backup:run
   ```

## Security Checklist

- [ ] SSL certificate installed and configured
- [ ] File permissions properly set
- [ ] Environment variables secured
- [ ] Debug mode disabled
- [ ] Error logging configured
- [ ] CSRF protection enabled
- [ ] Secure headers configured
- [ ] Rate limiting implemented
- [ ] Backup system configured

## Monitoring

1. **Error Logging**
   ```bash
   # Configure error logging in .env
   LOG_CHANNEL=stack
   LOG_LEVEL=error
   ```

2. **Performance Monitoring**
   ```bash
   # Install monitoring package
   composer require laravel/telescope --dev

   # Configure Telescope
   php artisan telescope:install
   php artisan migrate
   ```

## Maintenance

1. **Regular Updates**
   ```bash
   # Update dependencies
   composer update --no-dev
   npm update

   # Run migrations
   php artisan migrate --force
   ```

2. **Backup Management**
   ```bash
   # Manual backup
   php artisan backup:run

   # List backups
   php artisan backup:list
   ```

3. **Log Rotation**
   ```bash
   # Configure logrotate
   /path/to/blogposter/storage/logs/*.log {
       daily
       missingok
       rotate 7
       compress
       delaycompress
       notifempty
       create 0640 www-data www-data
   }
   ```

## Troubleshooting

1. **Common Issues**
   - Permission errors: Check storage and bootstrap/cache permissions
   - Cron job failures: Verify cron configuration and logs
   - SSL issues: Check certificate expiration and renewal
   - Database connection: Verify SQLite file permissions

2. **Logging**
   - Application logs: `/storage/logs/laravel.log`
   - Server logs: `/var/log/nginx/error.log` or `/var/log/apache2/error.log`
   - PHP-FPM logs: `/var/log/php8.1-fpm.log`

## Support

For additional support or questions:
- GitHub Issues: [Project Issues](https://github.com/yourusername/blogposter/issues)
- Documentation: [Project Wiki](https://github.com/yourusername/blogposter/wiki)
