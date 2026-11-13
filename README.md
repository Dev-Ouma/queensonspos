<p align="center">
  <img src="public/logo.svg" alt="QUEENSONS" width="220">
</p>

<h1 align="center">QUEENSONS POS</h1>

<p align="center">
  Fashion ecommerce storefront, in-store point of sale and back-office retail management, all in one Laravel app.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs&logoColor=white" alt="Vue 3">
  <img src="https://img.shields.io/badge/Inertia.js-2-9553E9" alt="Inertia 2">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/tests-Pest-F28D1A" alt="Pest">
</p>

---

## Overview

QUEENSONS is a modular monolith that runs a fashion retail business end to end:

- **Storefront** – a branded online shop where customers browse, buy and track orders.
- **POS terminal** – a fast till for in-store sales, returns and cash management.
- **Admin portal** – catalog, stock, purchasing, finance, CRM, marketing and content tools for staff.

One backend serves all three surfaces through Inertia.js, so there is no separate API or SPA auth to maintain.

## Features

### Storefront
- Home page with admin-editable hero, flash sale, testimonials, stats and announcements
- Smart search: plain words like "red dress under 3k" become filters, read by Claude when an API key is set or by a built-in parser otherwise
- Shop with categories, collections and brands; product pages with variants, size guide, reviews and recently viewed items
- Cart with free-delivery progress, coupons and checkout
- Customer accounts: orders, order tracking, wishlist and saved addresses
- WhatsApp chat and share touchpoints, SEO and Open Graph meta, branded 404 page

### Point of sale
- Register sessions with opening float, cash in/out movements and close-out
- Barcode and search-driven sale terminal with printable receipts
- Returns and refunds against original sales

### Back office
- **Catalog** – products, variants, images, categories, brands, collections
- **Inventory** – multi-warehouse and multi-branch balances, transactions, stock transfers, reservations
- **Procurement** – suppliers, purchase orders, goods received notes
- **Orders and delivery** – status history, deliveries, payments, webhook events, refunds
- **Finance** – expenses and expense categories, sales reports
- **CRM** – customers, loyalty points, product reviews, support tickets
- **Marketing and CMS** – coupons, banners, CMS pages, storefront content editor
- **Security** – role-based access with Spatie Permission and a full audit log

## Tech stack

| Layer      | Choice                                      |
|------------|---------------------------------------------|
| Backend    | Laravel 13, PHP 8.3+                         |
| Frontend   | Vue 3 (`<script setup>`) via Inertia.js 2    |
| Styling    | Tailwind CSS 3                               |
| Auth       | Laravel Breeze, Sanctum                      |
| RBAC       | spatie/laravel-permission                    |
| PDF        | barryvdh/laravel-dompdf (receipts, invoices) |
| Routing    | Ziggy (named routes in JS)                   |
| Build      | Vite                                         |
| Tests      | Pest                                         |
| Database   | PostgreSQL recommended; any Laravel driver works for local dev |

## Getting started

### Requirements
- PHP 8.3+ and Composer
- Node.js 20+ and npm
- A database (PostgreSQL 16 recommended)

### Install

```bash
git clone https://github.com/Dev-Ouma/queensonspos.git
cd queensonspos
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Set your database credentials in `.env`, then migrate and seed demo data:

```bash
php artisan migrate --seed
```

### Run

```bash
composer run dev
```

Or run the pieces yourself:

```bash
php artisan serve
npm run dev
```

Then open:

| Surface       | URL           |
|---------------|---------------|
| Storefront    | `/`           |
| Customer area | `/account`    |
| Admin portal  | `/admin`      |
| POS terminal  | `/pos`        |

Seeded staff accounts use demo credentials from `database/seeders`. Change them before any real deployment.

### AI search (optional)

Add these to `.env` to let Claude read shopper searches:

```dotenv
ANTHROPIC_API_KEY=your-key
# Optional: defaults to claude-opus-5-5
ANTHROPIC_SEARCH_MODEL=claude-opus-5-5
```

Without a key, a built-in parser handles categories, colours, sizes and prices. Switch AI search on or off in Admin → Website CMS, and see what shoppers search for in Admin → Search Insights. Each answer is cached for a day, and each visitor can trigger at most 20 new AI lookups a minute.

Search history older than six months is pruned daily by the scheduler, so run `php artisan schedule:run` every minute from cron.

## Testing

```bash
php artisan test
```

## Project structure

```
app/
  Http/Controllers/   Storefront, Account, Admin and POS controllers
  Models/             Eloquent models for every retail domain
database/
  migrations/         Schema
  seeders/            Demo and UAT data
resources/js/
  Pages/Storefront/   Public shop
  Pages/Account/      Customer portal
  Pages/Admin/        Back-office screens
  Pages/Pos/          Till and receipts
routes/web.php        All web routes
tests/                Pest feature and unit tests
```

## Roadmap

See [PLAN.md](PLAN.md) for the full implementation plan, including M-Pesa Daraja payments, Redis-backed stock locks and production deployment.

## License

Proprietary. Copyright © STACKGee Technologies. All rights reserved.
