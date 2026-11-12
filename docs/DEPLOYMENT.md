# Deployment, Security Hardening, Backup & Restore

## 1. Server requirements
- Ubuntu 22.04/24.04, Nginx + PHP 8.4 FPM (`php-fpm`, `php-mysql`, `php-mbstring`, `php-xml`, `php-gd`, `php-zip`, `php-curl`, `php-bcmath`)
- MySQL/MariaDB 8+, Node 20+ (build only), Composer 2
- HTTPS certificate (Let's Encrypt `certbot`) — **required**: the app enforces secure cookies and HSTS when `APP_URL` is `https://`

## 2. First deployment
```bash
cd /var/www && git clone <repo> amoleck && cd amoleck
cp .env.production.example .env
# Edit .env: APP_KEY via `php artisan key:generate`, DB_*, MAIL_*, APP_URL=https://...
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
```

## 3. Nginx vhost
```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/amoleck/public;
    index index.php;
    ssl_certificate     /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    client_max_body_size 20M;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
server { listen 80; server_name your-domain.com; return 301 https://$host$request_uri; }
```

## 4. Background services
**Cron** (scheduled reports, announcements):
```
* * * * * cd /var/www/amoleck && php artisan schedule:run >> /dev/null 2>&1
```
**Queue worker** (`/etc/supervisor/conf.d/amoleck.conf`):
```
[program:amoleck-worker]
command=php /var/www/amoleck/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
```

## 5. Subsequent deploys
`bash deploy.sh` — pulls, installs, builds, migrates, rebuilds caches, restarts workers.

## 6. Security posture (already enforced in code)
- `APP_DEBUG=false` is mandatory — production must never expose stack traces.
- **Security headers** on every web response: CSP, `X-Frame-Options: SAMEORIGIN`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, and **HSTS** once served over HTTPS (`SecurityHeaders` middleware).
- HTTPS is forced when `APP_ENV=production` and `APP_URL` starts with `https`.
- **Private storage**: documents, receipts and payslips live under `storage/app/` and are only served through authenticated, permission-checked download/preview endpoints. Never alias `storage/` in the web server.
- **Financial confidentiality**: accounting, payroll and management endpoints require explicit permissions — no role grants them implicitly. Assign `Accounting & Finance` / `Management & Analytics` permissions only to the specific employees who need them via Admin → Staff Access.
- External document links use unguessable tokens, optional passwords, expiries and access limits.
- Secrets: `.env` is gitignored — never commit it, and rotate `APP_KEY`/DB passwords if they ever leak. Database credentials should be a dedicated low-privilege user (`amoleck_app` with rights on `amoleck` only).

## 7. First-login checklist
1. Log in with the seeded admin (`Admin@123`) and **change the password immediately** (`must_change_password` forces this anyway).
2. Settings → set clinic details, currency, document upload limits.
3. Create employee accounts; assign roles + granular permissions (Accounting, Management, Documents, Board) per person.
4. Accounting: seed Chart of Accounts if not seeded (`AccountingService::seedCoA()`).
5. Create branches, departments, service catalogue, products.

## 8. Backups
Daily (cron):
```bash
mysqldump -u <user> -p<pass> amoleck | gzip > /backups/db-$(date +\%F).sql.gz
tar -czf /backups/files-$(date +\%F).tar.gz /var/www/amoleck/storage/app
```
Rotation: 7 daily, 4 weekly, 3 monthly; copy weekly off-server (encrypted — backups contain confidential financial/HR data).

## 9. Restore test (do it regularly)
```bash
gunzip < /backups/db-YYYY-MM-DD.sql.gz | mysql -u <user> -p amoleck_restore
tar -xzf /backups/files-YYYY-MM-DD.tar.gz -C /tmp/restore-check
```
A backup that has never been restored is not proven.
