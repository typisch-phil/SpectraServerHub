#!/bin/bash

# SpectraHost Deployment Script
# Usage: ./deploy.sh [environment]

set -e

ENVIRONMENT=${1:-production}
PROJECT_DIR="/var/www/html/spectrahost"
BACKUP_DIR="/var/backups/spectrahost"

echo "🚀 Starting SpectraHost deployment for $ENVIRONMENT environment..."

# Create backup
echo "📦 Creating backup..."
mkdir -p $BACKUP_DIR
tar -czf "$BACKUP_DIR/backup-$(date +%Y%m%d_%H%M%S).tar.gz" -C $PROJECT_DIR .

# Update from repository (if using git)
if [ -d "$PROJECT_DIR/.git" ]; then
    echo "🔄 Updating from repository..."
    cd $PROJECT_DIR
    git pull origin main
fi

# Set proper permissions
echo "🔐 Setting permissions..."
find $PROJECT_DIR -type f -exec chmod 644 {} \;
find $PROJECT_DIR -type d -exec chmod 755 {} \;
chmod 600 $PROJECT_DIR/.env

# Update database (if migrations exist)
if [ -f "$PROJECT_DIR/database/migrate.php" ]; then
    echo "🗄️ Running database migrations..."
    php $PROJECT_DIR/database/migrate.php
fi

# Clear cache (if cache system exists)
if [ -d "$PROJECT_DIR/cache" ]; then
    echo "🧹 Clearing cache..."
    rm -rf $PROJECT_DIR/cache/*
fi

# Restart web server
echo "🔄 Restarting web server..."
if command -v systemctl &> /dev/null; then
    if systemctl is-active --quiet apache2; then
        systemctl reload apache2
    elif systemctl is-active --quiet nginx; then
        systemctl reload nginx
    fi
fi

# Health check
echo "🏥 Performing health check..."
HEALTH_URL="https://$(basename $PROJECT_DIR).com"
if curl -f -s $HEALTH_URL > /dev/null; then
    echo "✅ Deployment successful!"
else
    echo "❌ Health check failed!"
    exit 1
fi

echo "🎉 SpectraHost deployment completed successfully!"