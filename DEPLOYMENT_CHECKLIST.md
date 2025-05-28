# Deployment Checklist

## Pre-Deployment Tasks

### 1. Code Review & Testing
- [ ] All feature tests passing
- [ ] All unit tests passing
- [ ] Code review completed
- [ ] Security vulnerabilities checked
- [ ] Dependencies up to date
- [ ] No debug code in production files

### 2. Environment Configuration
- [ ] Production .env file configured
  - [ ] APP_ENV set to production
  - [ ] APP_DEBUG set to false
  - [ ] Proper app URL set
  - [ ] Database credentials set
  - [ ] Mail settings configured
  - [ ] Queue connection configured
  - [ ] Google OAuth credentials set

### 3. Database Preparation
- [ ] Database backups configured
- [ ] Migrations tested
- [ ] Indexes optimized
- [ ] Database permissions set
- [ ] SQLite journal mode configured

### 4. Server Setup
- [ ] Server requirements met
  - [ ] PHP 8.1+ installed
  - [ ] Required PHP extensions enabled
  - [ ] Composer installed
  - [ ] Required system packages installed
- [ ] File permissions set correctly
  - [ ] storage/ directory writable
  - [ ] bootstrap/cache/ directory writable
  - [ ] Proper ownership set

### 5. Security Configuration
- [ ] SSL certificate installed
- [ ] Firewall configured
- [ ] Rate limiting enabled
- [ ] CSRF protection enabled
- [ ] Secure headers configured
- [ ] File permissions hardened

## Deployment Process

### 1. Initial Setup
- [ ] Run production-setup.sh
- [ ] Verify server configuration
- [ ] Check service status
  - [ ] Nginx
  - [ ] PHP-FPM
  - [ ] Redis
  - [ ] Supervisor

### 2. Application Deployment
- [ ] Put application in maintenance mode
- [ ] Back up current version
- [ ] Deploy new code
- [ ] Install dependencies
  ```bash
  composer install --no-dev --optimize-autoloader
  ```
- [ ] Clear and cache configurations
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] Run database migrations
  ```bash
  php artisan migrate --force
  ```
- [ ] Set proper permissions
- [ ] Take application out of maintenance mode

### 3. Monitoring Setup
- [ ] Configure monitor-system.sh
- [ ] Set up monitoring cron jobs
- [ ] Verify monitoring alerts
- [ ] Test error reporting
- [ ] Configure log rotation

### 4. Backup System
- [ ] Configure backup-system.sh
- [ ] Set up backup cron jobs
- [ ] Verify backup process
- [ ] Test backup restoration
- [ ] Configure offsite storage

### 5. Queue Workers
- [ ] Configure Supervisor
- [ ] Start queue workers
- [ ] Verify job processing
- [ ] Set up worker monitoring

## Post-Deployment Tasks

### 1. Verification
- [ ] Test all core features
  - [ ] Authentication working
  - [ ] Post creation/editing working
  - [ ] Scheduling system working
  - [ ] Analytics working
  - [ ] Notifications working
- [ ] Check all external integrations
  - [ ] Google OAuth working
  - [ ] Blogger API working
  - [ ] Email sending working
- [ ] Verify SSL configuration
- [ ] Check security headers
- [ ] Test backup system
- [ ] Verify monitoring system

### 2. Performance
- [ ] Run performance tests
- [ ] Check page load times
- [ ] Verify caching
- [ ] Monitor resource usage
- [ ] Test under load

### 3. Documentation
- [ ] Update deployment status
- [ ] Document any issues/solutions
- [ ] Update configuration docs
- [ ] Record deployment notes

### 4. Monitoring
- [ ] Set up uptime monitoring
- [ ] Configure error tracking
- [ ] Set up performance monitoring
- [ ] Configure alert thresholds
- [ ] Test notification system

## Emergency Procedures

### 1. Rollback Plan
- [ ] Backup restoration procedure documented
- [ ] Rollback scripts ready
- [ ] Team notified of procedures
- [ ] Test environment available

### 2. Emergency Contacts
- [ ] System administrators
- [ ] Database administrators
- [ ] Security team
- [ ] Service providers

### 3. Incident Response
- [ ] Logging system verified
- [ ] Error tracking configured
- [ ] Response procedures documented
- [ ] Communication channels established

## Final Approval
- [ ] Security team sign-off
- [ ] Operations team sign-off
- [ ] Development team sign-off
- [ ] Project manager sign-off
