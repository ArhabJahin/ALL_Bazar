# AllBazar

AllBazar is a Laravel MVC marketplace prototype for Bangladesh local shops. It combines local product discovery, smart product comparison, shop profiles, a shop map, cart and checkout screens, customer accounts, shop-owner tooling, and admin/co-admin management structure.

## Stack

- Frontend: Blade, HTML, CSS, JavaScript
- Backend: PHP 8.0+ with Laravel 9
- Database: MySQL through XAMPP

## Setup

1. Install Composer if it is not already available.
2. Copy `.env.example` to `.env`.
3. Create a MySQL database named `allbazar` in XAMPP/phpMyAdmin.
4. Run:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

## One-click Windows launcher

Double-click:

```text
Run_AllBazar.bat
```

It starts/checks XAMPP MySQL, creates the `allbazar` database, runs migrations, opens the browser, and starts Laravel at:

```text
http://127.0.0.1:8000
```

To stop the Laravel dev server, close the launcher window or double-click:

```text
Stop_AllBazar.bat
```

Sample accounts use the password `password`:

- `admin@allbazar.test`
- `coadmin@allbazar.test`
- `owner@allbazar.test`
- `customer@allbazar.test`

## Main Routes

- `/` homepage
- `/search?q=rice` smart grouped search
- `/advanced-search` advanced filters
- `/shops` shop map
- `/cart` and `/checkout`
- `/account`, `/shop-owner`, `/admin`
