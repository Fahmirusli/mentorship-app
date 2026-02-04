cd /var/www/mentorship/mentorship-backend
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
systemctl restart php8.2-fpm
cd /var/www/mentorship/mentorship-frontend
git pull
npm run build
pm2 restart mentorship-frontend
echo "Deployment complete!"
