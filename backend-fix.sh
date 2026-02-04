cd /var/www/mentorship/mentorship-backend
git reset --hard HEAD
git pull origin main
php artisan migrate --force
php artisan config:clear
systemctl restart php8.2-fpm
echo "Backend updated!"
