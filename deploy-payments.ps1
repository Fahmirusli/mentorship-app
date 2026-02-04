# Deploy payment fixes to production
$server = "root@209.97.162.99"
$password = "+Yv9U3+h*%w7PuQ"

Write-Host "Deploying payment system fixes..." -ForegroundColor Green

# Deploy backend
Write-Host "`nDeploying backend..." -ForegroundColor Yellow
ssh $server @"
cd /var/www/mentorship/mentorship-backend
git pull
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
systemctl restart php8.2-fpm
echo 'Backend deployed successfully'
"@

Write-Host "`nDeployment complete! Test payment flow at https://uplifts.dev" -ForegroundColor Green
