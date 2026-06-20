$ErrorActionPreference = "Stop"

Write-Host "Deploying all changes to production..." -ForegroundColor Green

# Deploy backend
Write-Host "`n=== Deploying Backend ===" -ForegroundColor Yellow
$backendCommands = @"
cd /var/www/mentorship/mentorship-backend
git pull origin main
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
systemctl restart php8.2-fpm
echo 'Backend deployed'
"@

$backendCommands | ssh root@168.144.43.160 /bin/bash

# Deploy frontend
Write-Host "`n=== Deploying Frontend ===" -ForegroundColor Yellow
$frontendCommands = @"
cd /var/www/mentorship/mentorship-frontend
git pull origin main
npm install --production
npm run build
pm2 restart mentorship-frontend
pm2 save
echo 'Frontend deployed'
"@

$frontendCommands | ssh root@168.144.43.160 /bin/bash

Write-Host "`n=== Deployment Complete ===" -ForegroundColor Green
Write-Host "Frontend: https://uplifts.dev" -ForegroundColor Cyan
Write-Host "Backend: https://api.uplifts.dev" -ForegroundColor Cyan
