# Installation Windows

## Prerequisites Verified
- PHP 8.2.12 (cli) Zend Engine v4.2.12, extensions: pdo_mysql, openssl, mbstring, fileinfo, ctype, json, bcmath, tokenizer, xml
- Composer 2.10.2
- Node v24.14.0, npm 11.17.0
- MySQL 10.4.32-MariaDB (XAMPP) on 3306 LISTENING
- Git 2.50.0

## Laravel Selection
- Verified PHP 8.2.12 supports Laravel 12.68.0 (laravel/framework ^12.0 requires PHP ^8.2)
- Created via `composer create-project laravel/laravel dashboard` -> Laravel 12.68.0

## Steps
1. `git clone <repo>` then `cd ai_classroom_cheat_detection`
2. `composer install` (if not already)
3. `cd dashboard`
4. Copy `.env.example` to `.env` and configure:
   ```
   APP_NAME="AI Classroom"
   APP_KEY= (via php artisan key:generate)
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ai_classroom
   DB_USERNAME=root
   DB_PASSWORD=
   ```
5. `C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE ai_classroom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`
6. `php artisan key:generate`
7. `php artisan migrate --force`
8. `php artisan db:seed --class=RolePermissionSeeder` (local/testing only, creates demo users Password123!)
9. `composer require laravel/breeze --dev` + `php artisan breeze:install blade --dark --pest`
10. `npm install && npm run build`
11. `php artisan serve --host=127.0.0.1 --port=8000` or via XAMPP Apache pointing to `dashboard/public`
12. Login with admin@example.com / Password123! (local only)

## XAMPP
- Start MySQL via XAMPP Control Panel, ensure 3306 LISTENING
- Apache DocumentRoot can point to dashboard/public for production-like

## Troubleshooting
- If `composer install` timeout, run `composer install --prefer-dist`
- If `vite build` fails, ensure Node 24+ and `npm install`
- If `php artisan migrate` fails, check DB_DATABASE exists and DB_PASSWORD empty for XAMPP
