#!/bin/bash

# Backup System Script

# Configuration
BACKUP_DIR="/var/backups/blogposter"
APP_DIR="/var/www/blogposter"
DB_FILE="$APP_DIR/database/database.sqlite"
MAX_BACKUPS=7
S3_BUCKET="your-backup-bucket"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="blogposter_backup_$TIMESTAMP"

# Ensure backup directory exists
mkdir -p "$BACKUP_DIR"
mkdir -p "$BACKUP_DIR/daily"
mkdir -p "$BACKUP_DIR/weekly"
mkdir -p "$BACKUP_DIR/monthly"

# Function to send notification
send_notification() {
    local subject="$1"
    local message="$2"
    echo "$message" | mail -s "$subject" admin@your-domain.com
}

# Function to cleanup old backups
cleanup_old_backups() {
    local directory="$1"
    local keep="$2"
    
    # Delete old backups, keeping the specified number
    cd "$directory" && ls -t | tail -n +$((keep + 1)) | xargs -r rm
}

# Create backup directory for this run
BACKUP_PATH="$BACKUP_DIR/daily/$BACKUP_NAME"
mkdir -p "$BACKUP_PATH"

echo "Starting backup process..."

# Put application in maintenance mode
cd "$APP_DIR" && php artisan down

# Backup database
echo "Backing up database..."
if [ -f "$DB_FILE" ]; then
    sqlite3 "$DB_FILE" ".backup '$BACKUP_PATH/database.sqlite'"
    if [ $? -eq 0 ]; then
        echo "Database backup successful"
    else
        send_notification "Backup Failed" "Database backup failed for $BACKUP_NAME"
        php artisan up
        exit 1
    fi
else
    send_notification "Backup Failed" "Database file not found"
    php artisan up
    exit 1
fi

# Backup application files
echo "Backing up application files..."
tar -czf "$BACKUP_PATH/files.tar.gz" \
    --exclude="$APP_DIR/vendor" \
    --exclude="$APP_DIR/node_modules" \
    --exclude="$APP_DIR/storage/logs/*" \
    --exclude="$APP_DIR/.git" \
    -C "$APP_DIR" .

if [ $? -eq 0 ]; then
    echo "Application files backup successful"
else
    send_notification "Backup Failed" "Files backup failed for $BACKUP_NAME"
    php artisan up
    exit 1
fi

# Take application out of maintenance mode
cd "$APP_DIR" && php artisan up

# Create weekly backup (on Sundays)
if [ $(date +%u) -eq 7 ]; then
    echo "Creating weekly backup..."
    cp -r "$BACKUP_PATH" "$BACKUP_DIR/weekly/"
fi

# Create monthly backup (on 1st of the month)
if [ $(date +%d) -eq 01 ]; then
    echo "Creating monthly backup..."
    cp -r "$BACKUP_PATH" "$BACKUP_DIR/monthly/"
fi

# Upload to S3
echo "Uploading backup to S3..."
if command -v aws &> /dev/null; then
    aws s3 cp "$BACKUP_PATH" "s3://$S3_BUCKET/daily/$BACKUP_NAME" --recursive
    if [ $(date +%u) -eq 7 ]; then
        aws s3 cp "$BACKUP_PATH" "s3://$S3_BUCKET/weekly/$BACKUP_NAME" --recursive
    fi
    if [ $(date +%d) -eq 01 ]; then
        aws s3 cp "$BACKUP_PATH" "s3://$S3_BUCKET/monthly/$BACKUP_NAME" --recursive
    fi
else
    echo "AWS CLI not found, skipping S3 upload"
fi

# Cleanup old backups
echo "Cleaning up old backups..."
cleanup_old_backups "$BACKUP_DIR/daily" $MAX_BACKUPS
cleanup_old_backups "$BACKUP_DIR/weekly" 4
cleanup_old_backups "$BACKUP_DIR/monthly" 3

# Verify backup
echo "Verifying backup..."
if [ -f "$BACKUP_PATH/database.sqlite" ] && [ -f "$BACKUP_PATH/files.tar.gz" ]; then
    # Check files are not empty
    if [ -s "$BACKUP_PATH/database.sqlite" ] && [ -s "$BACKUP_PATH/files.tar.gz" ]; then
        echo "Backup verification successful"
        send_notification "Backup Successful" "Backup $BACKUP_NAME completed successfully"
    else
        send_notification "Backup Failed" "Backup files are empty for $BACKUP_NAME"
        exit 1
    fi
else
    send_notification "Backup Failed" "Backup files are missing for $BACKUP_NAME"
    exit 1
fi

# Generate backup report
REPORT="$BACKUP_PATH/backup_report.txt"
echo "Backup Report for $BACKUP_NAME" > "$REPORT"
echo "--------------------------------" >> "$REPORT"
echo "Date: $(date)" >> "$REPORT"
echo "Database Size: $(du -h "$BACKUP_PATH/database.sqlite" | cut -f1)" >> "$REPORT"
echo "Files Size: $(du -h "$BACKUP_PATH/files.tar.gz" | cut -f1)" >> "$REPORT"
echo "Total Size: $(du -h "$BACKUP_PATH" | tail -n1 | cut -f1)" >> "$REPORT"

echo "Backup process completed successfully!"
