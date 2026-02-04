#!/bin/bash
set -e

echo "=== Deploying Backend ==="
cd /var/www/mentorship/mentorship-backend
git pull origin main
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
systemctl restart php8.2-fpm
echo "Backend deployed"

echo ""
echo "=== Deploying Frontend ==="
cd /var/www/mentorship/mentorship-frontend
git pull origin main
npm install --production
npm run build
pm2 restart mentorship-frontend
pm2 save
echo "Frontend deployed"

echo ""
echo "=== Deployment Complete ==="
