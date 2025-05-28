#!/bin/bash

# System Monitoring Script

# Configuration
APP_DIR="/var/www/blogposter"
LOG_DIR="$APP_DIR/storage/logs"
MONITOR_LOG="$LOG_DIR/monitoring.log"
ALERT_EMAIL="admin@your-domain.com"
THRESHOLD_CPU=80
THRESHOLD_MEMORY=80
THRESHOLD_DISK=90
THRESHOLD_LOAD=5

# Create monitoring log directory if it doesn't exist
mkdir -p "$LOG_DIR"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$MONITOR_LOG"
}

# Function to send alert
send_alert() {
    local subject="$1"
    local message="$2"
    echo "$message" | mail -s "BlogPoster Alert: $subject" "$ALERT_EMAIL"
    log_message "Alert sent: $subject - $message"
}

# Check system resources
check_system_resources() {
    log_message "Checking system resources..."

    # CPU Usage
    CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d. -f1)
    if [ "$CPU_USAGE" -gt "$THRESHOLD_CPU" ]; then
        send_alert "High CPU Usage" "CPU usage is at ${CPU_USAGE}%"
    fi

    # Memory Usage
    MEMORY_USAGE=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
    if [ "$MEMORY_USAGE" -gt "$THRESHOLD_MEMORY" ]; then
        send_alert "High Memory Usage" "Memory usage is at ${MEMORY_USAGE}%"
    fi

    # Disk Usage
    DISK_USAGE=$(df -h / | awk 'NR==2 {print int($5)}')
    if [ "$DISK_USAGE" -gt "$THRESHOLD_DISK" ]; then
        send_alert "High Disk Usage" "Disk usage is at ${DISK_USAGE}%"
    fi

    # System Load
    LOAD_AVERAGE=$(uptime | awk -F'load average:' '{ print $2 }' | cut -d, -f1 | xargs)
    if (( $(echo "$LOAD_AVERAGE > $THRESHOLD_LOAD" | bc -l) )); then
        send_alert "High System Load" "Load average is $LOAD_AVERAGE"
    fi
}

# Check application health
check_application_health() {
    log_message "Checking application health..."

    # Check Laravel Queue
    FAILED_JOBS=$(cd "$APP_DIR" && php artisan queue:failed --count)
    if [ "$FAILED_JOBS" -gt 0 ]; then
        send_alert "Failed Jobs" "There are $FAILED_JOBS failed jobs in the queue"
    fi

    # Check Laravel Storage Permissions
    if [ ! -w "$APP_DIR/storage" ]; then
        send_alert "Permission Error" "Storage directory is not writable"
    fi

    # Check Laravel Log Size
    LOG_SIZE=$(du -m "$LOG_DIR/laravel.log" 2>/dev/null | cut -f1)
    if [ ! -z "$LOG_SIZE" ] && [ "$LOG_SIZE" -gt 100 ]; then
        send_alert "Large Log File" "Laravel log file is ${LOG_SIZE}MB"
    fi

    # Check Recent Error Logs
    ERROR_COUNT=$(grep -c "\\[ERROR\\]\\|\\[ALERT\\]\\|\\[CRITICAL\\]" "$LOG_DIR/laravel.log" 2>/dev/null)
    if [ "$ERROR_COUNT" -gt 0 ]; then
        send_alert "Application Errors" "Found $ERROR_COUNT errors in the log"
    fi
}

# Check database health
check_database_health() {
    log_message "Checking database health..."

    # Check SQLite database file
    DB_FILE="$APP_DIR/database/database.sqlite"
    if [ ! -f "$DB_FILE" ]; then
        send_alert "Database Error" "Database file not found"
    elif [ ! -r "$DB_FILE" ]; then
        send_alert "Database Error" "Database file not readable"
    fi

    # Check database size
    DB_SIZE=$(du -m "$DB_FILE" 2>/dev/null | cut -f1)
    if [ ! -z "$DB_SIZE" ] && [ "$DB_SIZE" -gt 1000 ]; then
        send_alert "Large Database" "Database size is ${DB_SIZE}MB"
    fi

    # Check database integrity
    if ! sqlite3 "$DB_FILE" "PRAGMA integrity_check;" > /dev/null 2>&1; then
        send_alert "Database Error" "Database integrity check failed"
    fi
}

# Check services status
check_services() {
    log_message "Checking services status..."

    # Check Nginx
    if ! systemctl is-active --quiet nginx; then
        send_alert "Service Down" "Nginx is not running"
    fi

    # Check PHP-FPM
    if ! systemctl is-active --quiet php8.1-fpm; then
        send_alert "Service Down" "PHP-FPM is not running"
    fi

    # Check Redis
    if ! systemctl is-active --quiet redis-server; then
        send_alert "Service Down" "Redis is not running"
    fi

    # Check Supervisor
    if ! systemctl is-active --quiet supervisor; then
        send_alert "Service Down" "Supervisor is not running"
    fi
}

# Check SSL certificate
check_ssl() {
    log_message "Checking SSL certificate..."

    DOMAIN="your-domain.com"
    CERT_EXPIRY=$(openssl s_client -servername $DOMAIN -connect $DOMAIN:443 2>/dev/null | openssl x509 -noout -enddate | cut -d= -f2)
    EXPIRY_DATE=$(date -d "$CERT_EXPIRY" +%s)
    CURRENT_DATE=$(date +%s)
    DAYS_REMAINING=$(( ($EXPIRY_DATE - $CURRENT_DATE) / 86400 ))

    if [ "$DAYS_REMAINING" -lt 30 ]; then
        send_alert "SSL Certificate Expiring" "SSL certificate will expire in $DAYS_REMAINING days"
    fi
}

# Generate monitoring report
generate_report() {
    REPORT="$LOG_DIR/monitoring_report.txt"
    echo "BlogPoster Monitoring Report" > "$REPORT"
    echo "Generated: $(date)" >> "$REPORT"
    echo "----------------------------------------" >> "$REPORT"
    
    echo "System Resources:" >> "$REPORT"
    echo "CPU Usage: ${CPU_USAGE}%" >> "$REPORT"
    echo "Memory Usage: ${MEMORY_USAGE}%" >> "$REPORT"
    echo "Disk Usage: ${DISK_USAGE}%" >> "$REPORT"
    echo "Load Average: $LOAD_AVERAGE" >> "$REPORT"
    
    echo "----------------------------------------" >> "$REPORT"
    echo "Application Status:" >> "$REPORT"
    echo "Failed Jobs: $FAILED_JOBS" >> "$REPORT"
    echo "Error Count: $ERROR_COUNT" >> "$REPORT"
    echo "Database Size: ${DB_SIZE}MB" >> "$REPORT"
    echo "Log Size: ${LOG_SIZE}MB" >> "$REPORT"
    
    # Send report via email
    cat "$REPORT" | mail -s "BlogPoster Daily Monitoring Report" "$ALERT_EMAIL"
}

# Main execution
log_message "Starting monitoring checks..."

check_system_resources
check_application_health
check_database_health
check_services
check_ssl
generate_report

log_message "Monitoring checks completed"

# Rotate monitoring log if it gets too large
if [ -f "$MONITOR_LOG" ] && [ $(stat -f%z "$MONITOR_LOG") -gt 10485760 ]; then
    mv "$MONITOR_LOG" "$MONITOR_LOG.$(date +%Y%m%d)"
    gzip "$MONITOR_LOG.$(date +%Y%m%d)"
fi
