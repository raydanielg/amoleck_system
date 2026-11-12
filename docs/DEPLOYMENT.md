# Deployment, Backup & Restore

## Production checklist
1. Ubuntu + Nginx/Apache, PHP 8.3+, MySQL/MariaDB.
2. Point web root to `public/`. Enable HTTPS (Let's Encrypt).
3. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL`, DB + mail credentials.
4. `composer install --no-dev --optimize-autoloader`
5. `npm ci && npm run build`
6. `php artisan migrate --force --seed`
7. `php artisan config:cache route:cache view:cache`
8. `chown -R www-data:www-data storage bootstrap/cache`
9. Cron: `* * * * * php /var/www/app/artisan schedule:run >> /dev/null 2>&1`

## Receipts & uploads
Receipts are stored in `storage/app/receipts/` (private, served only via authenticated download route). Never expose `storage/app` publicly.

## Backups
Daily (cron):
```bash
mysqldump -u <user> -p<pass> amoleck_clinic | gzip > /backups/db-$(date +\%F).sql.gz
tar -czf /backups/files-$(date +\%F).tar.gz /var/www/app/storage/app
```
Rotation suggestion: keep 7 daily, 4 weekly, 3 monthly. Copy weekly off-server.

## Restore test (do it regularly)
```bash
gunzip < /backups/db-YYYY-MM-DD.sql.gz | mysql -u <user> -p amoleck_clinic_restore
tar -xzf /backups/files-YYYY-MM-DD.tar.gz -C /tmp/restore-check
```
A backup that has never been restored is not proven.
