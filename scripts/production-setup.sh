#!/bin/bash

# Production Environment Setup Script

echo "Starting production environment setup..."

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root"
    exit 1
fi

# Update system
echo "Updating system packages..."
apt-get update
apt-get upgrade -y

# Install required packages
echo "Installing required packages..."
apt-get install -y \
    nginx \
    redis-server \
    supervisor \
    certbot \
    python3-certbot-nginx \
    logrotate \
    fail2ban

# Install PHP and extensions
echo "Installing PHP and extensions..."
apt-get install -y \
    php8.1-fpm \
    php8.1-cli \
    php8.1-common \
    php8.1-mysql \
    php8.1-zip \
    php8.1-gd \
    php8.1-mbstring \
    php8.1-curl \
    php8.1-xml \
    php8.1-bcmath \
    php8.1-sqlite3 \
    php8.1-redis

# Configure PHP
echo "Configuring PHP..."
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.1/fpm/php.ini
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/8.1/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/8.1/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 60/' /etc/php/8.1/fpm/php.ini

# Configure OPcache
echo "Configuring OPcache..."
cat > /etc/php/8.1/mods-available/opcache.ini << 'EOL'
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=1
EOL

# Configure Redis
echo "Configuring Redis..."
sed -i 's/# maxmemory .*/maxmemory 128mb/' /etc/redis/redis.conf
sed -i 's/# maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf

# Configure Nginx
echo "Configuring Nginx..."
cat > /etc/nginx/sites-available/blogposter << 'EOL'
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/blogposter/public;

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
EOL

ln -s /etc/nginx/sites-available/blogposter /etc/nginx/sites-enabled/

# Configure Supervisor
echo "Configuring Supervisor..."
cat > /etc/supervisor/conf.d/blogposter-worker.conf << 'EOL'
[program:blogposter-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/blogposter/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/blogposter/storage/logs/worker.log
stopwaitsecs=3600
EOL

# Configure Logrotate
echo "Configuring Logrotate..."
cat > /etc/logrotate.d/blogposter << 'EOL'
/var/www/blogposter/storage/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
EOL

# Configure Fail2ban
echo "Configuring Fail2ban..."
cat > /etc/fail2ban/jail.local << 'EOL'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[nginx-http-auth]
enabled = true

[nginx-botsearch]
enabled = true
EOL

# Set up monitoring
echo "Setting up monitoring..."
mkdir -p /var/www/blogposter/monitoring
cat > /var/www/blogposter/monitoring/check-services.sh << 'EOL'
#!/bin/bash

# Check Nginx
if ! systemctl is-active --quiet nginx; then
    echo "Nginx is down!" | mail -s "Service Alert" admin@your-domain.com
fi

# Check PHP-FPM
if ! systemctl is-active --quiet php8.1-fpm; then
    echo "PHP-FPM is down!" | mail -s "Service Alert" admin@your-domain.com
fi

# Check Redis
if ! systemctl is-active --quiet redis-server; then
    echo "Redis is down!" | mail -s "Service Alert" admin@your-domain.com
fi

# Check Queue Worker
if ! supervisorctl status blogposter-worker:* | grep -q RUNNING; then
    echo "Queue worker is down!" | mail -s "Service Alert" admin@your-domain.com
fi
EOL

chmod +x /var/www/blogposter/monitoring/check-services.sh

# Add monitoring cron job
echo "*/5 * * * * root /var/www/blogposter/monitoring/check-services.sh" > /etc/cron.d/blogposter-monitoring

# Restart services
echo "Restarting services..."
systemctl restart php8.1-fpm
systemctl restart nginx
systemctl restart redis-server
supervisorctl reread
supervisorctl update
supervisorctl start all
service fail2ban restart

echo "Production environment setup completed!"
echo "Next steps:"
echo "1. Update domain name in Nginx configuration"
echo "2. Install SSL certificate using certbot"
echo "3. Update environment variables"
echo "4. Run database migrations"
echo "5. Set up backup system"
