# QUEENSONS — Implementation Plan v1.0

**Project:** QUEENSONS Fashion Ecommerce & Retail Management System
**Stack:** Laravel 11 + Inertia.js + Vue 3 + PostgreSQL 16
**Initial deployment:** `queensons.stackgee.com`
**Document owner:** STACKGee Technologies
**Status:** Draft — pending sign-off before Phase 1 begins.

---

## 1. Technology decisions

| Layer | Choice | Reason |
|---|---|---|
| Framework | Laravel 11 (PHP 8.3) | Mature retail/admin ecosystem, built-in auth, queues, policies, mail, filesystem. |
| Frontend transport | Inertia.js | Single codebase, server-driven routing, no separate SPA auth story. |
| UI | Vue 3 + Composition API + `<script setup>` | Reactive admin, storefront, and POS screens. |
| Styling | Tailwind CSS 3 + headless UI primitives | Rapid, consistent brand system. |
| Database | PostgreSQL 16 | SRDS §14.1 forbids SQLite; needs transactional stock integrity. |
| ORM / migrations | Eloquent + Laravel migrations | Versioned schema, model events for audit. |
| Queue / cache / locks | Redis | Checkout stock reservation locks, webhook idempotency keys, notification jobs. |
| Search | PostgreSQL full-text (phase 2) → Meilisearch (phase 8 if needed) | Avoid premature ES complexity. |
| Auth | Laravel Fortify + Sanctum (API) + Spatie Permission (RBAC) | MFA, session + token auth, role/permission matrix. |
| Payments | Laravel service classes per provider (M-Pesa Daraja, card) | Pluggable provider contract. |
| File storage | S3-compatible (local disk dev, DigitalOcean Spaces / R2 prod) | Product images, invoices, exports. |
| Observability | Laravel Telescope (non-prod) + Sentry (prod) + structured JSON logs | Audit, perf, error tracking. |
| Testing | Pest (feature + unit), Dusk (browser), Playwright for POS flows | Enforce "Definition of Done" (§15). |
| CI/CD | GitHub Actions → deploy to staging → UAT → prod | Rollback via tagged releases. |

**Explicitly rejected / deferred:**
- Microservices split per module — modular monolith instead, same DB, Laravel modules boundary.
- Separate Node POS — POS is an Inertia subdomain app sharing the backend.
- NoSQL for catalog — Postgres JSONB covers dynamic attributes.

## 2. Repository layout

Single Laravel monorepo, modular by domain:

```
pos/                              # working directory, becomes `queensons` repo
├── app/
│   ├── Domain/                   # domain-driven modules
│   │   ├── Catalog/              # Module 03
│   │   ├── Inventory/            # Module 04
│   │   ├── Orders/               # Module 06
│   │   ├── Payments/             # Module 07
│   │   ├── Pos/                  # Module 05
│   │   ├── Procurement/          # Module 09
│   │   ├── Finance/              # Module 10
│   │   ├── Crm/                  # Module 11
│   │   ├── Marketing/            # Module 12
│   │   ├── Cms/                  # Module 13
│   │   ├── Delivery/             # Module 08
│   │   ├── Identity/             # Module 04 (users/roles)
│   │   └── Audit/                # Module 19.5
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Storefront/       # /* public ecommerce
│   │   │   ├── Account/          # /account/*
│   │   │   ├── Admin/            # /admin/*
│   │   │   └── Pos/              # pos subdomain
│   │   └── Middleware/
│   └── Support/                  # cross-cutting helpers
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   ├── Storefront/
│   │   │   ├── Account/
│   │   │   ├── Admin/
│   │   │   └── Pos/
│   │   ├── Components/           # shared UI (brand tokens)
│   │   ├── Layouts/
│   │   └── stores/               # Pinia stores for POS cart
│   └── css/
├── routes/
│   ├── web.php                   # storefront + account
│   ├── admin.php                 # /admin
│   ├── pos.php                   # pos subdomain
│   └── api.php                   # webhooks, mobile future
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── Browser/
├── docker/                       # local dev stack
└── docs/                         # ADRs, API docs
```

Routing by host + prefix:
- `queensons.stackgee.com/` → storefront
- `queensons.stackgee.com/account/*` → customer portal
- `queensons.stackgee.com/admin/*` → admin
- `pos.stackgee.com/` → POS app

All four share one DB and one auth table but separate guards.

## 3. Data model — core tables (first-pass)

Grouped by SRDS §7. Only principal columns listed; full DDL to land in Phase 1 migrations.

### 3.1 Identity
- `users` (id, name, email, phone, password_hash, mfa_secret, status, last_login_at)
- `roles`, `permissions`, `role_permission`, `user_role` (Spatie)
- `branches`, `branch_user`
- `sessions`, `audit_logs` (actor_id, action, subject_type, subject_id, diff_json, ip, ua, at)

### 3.2 Catalog
- `categories` (parent_id, slug, name, published, position)
- `brands`
- `products` (sku_root, name, slug, brand_id, description, status, published_at, seo_json)
- `product_category` pivot
- `attributes`, `attribute_values` (configurable per product-type, JSONB fallback)
- `product_variants` (product_id, sku UNIQUE, barcode, size, color, price_cents, cost_cents, weight_g, status)
- `product_images` (product_id, variant_id nullable, path, position, alt)
- `collections`, `collection_product`
- `product_reviews` (status moderated)

### 3.3 Inventory (single source of truth)
- `warehouses`
- `inventory_balances` (variant_id, location_type, location_id, on_hand, reserved, damaged) — denormalized aggregate
- `inventory_transactions` **(immutable ledger)**: id, variant_id, qty_delta, movement_type, source_type/id, dest_type/id, actor_id, reference, reason, created_at
- `stock_reservations` (cart_id or order_id, variant_id, qty, expires_at, status)
- `stock_transfers`, `stock_transfer_items`
- `stock_counts`, `stock_count_items`

All balance mutations go through a `RecordInventoryMovement` action inside a DB transaction with row-level locks (`SELECT … FOR UPDATE`) on the balance row.

### 3.4 Ecommerce
- `customers` (separate from staff `users`), `customer_addresses`
- `carts`, `cart_items` (variant_id, qty, price_snapshot)
- `wishlists`, `wishlist_items`
- `orders` (reference UNIQUE, customer_id, status, totals, delivery_address_json, placed_at)
- `order_items` (variant_id, qty, unit_price, line_total, cost_snapshot)
- `order_status_history`
- `coupons`, `promotions`, `promotion_rules`

### 3.5 POS
- `registers` (branch_id, name, status)
- `pos_sessions` (register_id, cashier_id, opened_at, closed_at, opening_cash, closing_cash, expected_cash, variance, supervisor_id)
- `pos_sales` (session_id, reference UNIQUE, status, totals)
- `pos_sale_items`, `pos_cash_movements`
- `pos_returns`, `pos_exchanges`, `pos_refunds` (each references original `pos_sale_id`)

### 3.6 Payments
- `payments` (reference UNIQUE, subject_type[order|pos_sale], subject_id, amount_cents, method, provider, provider_txn_id UNIQUE, status, verified_at, raw_payload_json)
- `refunds` (payment_id, amount_cents, status, provider_refund_id UNIQUE)
- `payment_webhook_events` (provider, event_id UNIQUE, received_at, processed_at, status) — **idempotency key table**

### 3.7 Procurement / finance
- `suppliers`, `supplier_products`
- `purchase_requisitions`, `purchase_orders`, `purchase_order_items`
- `goods_received_notes`, `grn_items`
- `supplier_invoices`, `supplier_payments`
- `expenses`, `expense_categories`
- `cash_movements` (branch-level)

### 3.8 Operations
- `deliveries`, `couriers`, `delivery_assignments`
- `notifications` (polymorphic subject, channel, status)
- `support_tickets`, `ticket_messages`
- `settings` (key/value scoped)
- `backup_runs`

### 3.9 Integrity rules
- All money as `bigint` cents; display formatting in app layer only.
- UNIQUE: `products.slug`, `product_variants.sku`, `orders.reference`, `payments.reference`, `payments.provider_txn_id`.
- No row-level deletes on `inventory_transactions`, `payments`, `pos_sales`, `orders` once confirmed — soft-archive only.
- FK with `ON DELETE RESTRICT` for financial references.
- CHECK constraints on `qty_delta != 0`, `amount_cents > 0`, status enums via Laravel enum casts + DB check.

## 4. Module ↔ Phase ↔ Deliverable matrix

| Phase | Modules in scope (SRDS) | Backend deliverables | Frontend deliverables | Exit criteria |
|---|---|---|---|---|
| **P1 — Foundation** | 15, 19, 19.5 | Laravel skeleton, PG + Redis, migrations infra, auth + Fortify + MFA, Spatie RBAC seeded with 15 roles from §4.1, `settings` service, audit log listener, mail/SMS drivers, brand design tokens, Docker dev stack, CI pipeline | Login, MFA, admin shell (sidebar + topbar), base Tailwind theme with Royal Navy / Emerald / Champagne, Quicksand font | Can log in as any seeded role; audit log records every write; `php artisan migrate:fresh --seed` idempotent; CI green. |
| **P2 — Catalog & storefront** | 01, 03, 13 (partial) | Category tree, brand, product, variant, attribute, collection CRUD; image uploads to S3; bulk CSV import; CMS blocks for homepage/banners | Storefront homepage, category pages, PDP with variant picker, search + filter + sort, cart (session-backed), guest checkout page | Create a product with 3×6 variants from admin; shows on storefront; filters work; cart totals correct. |
| **P3 — Customer portal** | 02 | Customer auth (separate guard), addresses, wishlist, review submission + moderation queue | `/account/*` dashboard, order list, wishlist, review UI, email verify flow | Customer can register, verify email, place a dummy order against seeded stock, see it in history. |
| **P4 — Inventory & procurement** | 04, 09 | Inventory ledger + balance aggregate, reservations w/ expiry, transfer workflow, suppliers, PO → GRN → supplier invoice, stock alerts job | Admin inventory screens (per-variant, per-branch), PO builder, GRN receiving UI, transfer UI | Concurrent purchase test (10 parallel buys for stock=5) sells exactly 5, rejects 5; PO received updates inventory + cost. |
| **P5 — POS** | 05 | POS auth guard, register sessions, sales flow w/ stock lock, returns referencing original sale, cash reconciliation, offline sync endpoint with idempotency | POS SPA (Vue + Pinia) on `pos.stackgee.com`, barcode input, cart, payment picker, receipt print (ESC/POS + PDF), end-of-day screen | Cashier opens register, sells, closes with variance report; sale decrements same inventory as storefront; refund traceable to original. |
| **P6 — Orders, payments, delivery** | 06, 07, 08 | Order state machine (16 statuses per §10.1), Daraja STK push + callback, card provider contract, refund flow, webhook idempotency, delivery zones + courier assignment, dispatch workflow | Admin orders board, payment verification screen, dispatch UI, customer order tracking page, email + SMS templates | Full happy-path: cart → STK push → confirmed webhook → order paid → dispatched → delivered → completed. No duplicate payment on webhook replay. |
| **P7 — Finance** | 10 | Expense CRUD w/ approval, cash movements, register reconciliation, revenue + COGS + gross profit reporting, branch P&L, PDF/XLSX export | Finance dashboard, expense UI, reconciliation UI, report builder with date/branch/category filters | Reports match hand-calculated figures on a seeded dataset; exports respect active filters. |
| **P8 — Marketing, CRM, analytics** | 11, 12, 14 | Promotions engine (percent / fixed / BOGO / min-order / free delivery), coupons, loyalty points ledger, segmentation queries, campaign scheduler, analytics aggregates | Admin promotion builder, loyalty admin, business dashboard with charts (Chart.js) | Promotion applies correctly at checkout and POS; loyalty points earn/redeem balance; dashboard loads under 2s on 100k orders seed. |
| **P9 — Hardening & UAT** | 29 (all §29) | Security audit (OWASP), rate limiting, pentest fixes, backup + restore drill, performance tuning, production migration plan | A11y pass (WCAG 2.1 AA), responsive QA across Chrome/Safari/Firefox/Edge | §29.6 acceptance criteria 1–10 all green. |

## 5. Cross-cutting concerns

### 5.1 Inventory integrity (SRDS §8, §14)
- Every sale (storefront or POS) runs inside `DB::transaction`.
- Lock order: `inventory_balances` row FOR UPDATE → write `inventory_transactions` → update balance → write order/sale rows.
- Reservations created on `cart→checkout` with configurable TTL (default 15 min); scheduled job releases expired rows.
- Payment webhook converts reservation → confirmed deduction. Browser redirect alone never does.

### 5.2 Payments (SRDS §11)
- Interface `PaymentProvider` with `initiate`, `verify`, `refund`, `handleWebhook`.
- Implementations: `MpesaDarajaProvider`, `CardProvider`, `CashProvider`, `BankTransferProvider`.
- `payment_webhook_events.event_id` UNIQUE guarantees idempotency.
- Verification is pull-based: on webhook, re-query provider before marking paid.

### 5.3 RBAC
- Spatie permissions keyed as `module.action` (e.g. `orders.refund`, `inventory.adjust`).
- Policies per domain model; admin UI hides unpermitted buttons AND server re-checks.
- Sensitive actions (refund over threshold, void sale, inventory adjust) require supervisor PIN (second-factor at action-time, not just session MFA).

### 5.4 Audit (SRDS §19.5)
- Eloquent observer on all domain models writes to `audit_logs` with before/after diff.
- Append-only: no update/delete routes exposed; retention 7 years.

### 5.5 Multi-channel inventory sync
- Storefront, admin, and POS all call the same `SellInventory` action.
- No denormalized "website stock" vs "shop stock" — balances are per (variant, location). Available-to-sell = `on_hand - reserved - damaged`.

### 5.6 Future-proofing (SRDS §13)
- No hardcoded "women's fashion" — categories drive navigation.
- Currency stored per setting; schema already uses cents.
- Branch-scoped queries throughout — adding a branch = seed row, no code change.

## 6. Environments

| Env | Host | DB | Purpose |
|---|---|---|---|
| Local | docker-compose | PG 16 container | Dev + unit tests |
| CI | GitHub Actions | PG 16 service | Automated tests on PR |
| Staging / UAT | `queensons.stackgee.com` | Managed PG | SRDS §11 UAT |
| Production | `queensons.com` | Managed PG + replica | Live (post sign-off) |

Secrets via Laravel `.env` + deploy-time injection (no secrets in repo).

## 7. Open questions (need business/owner input before P1 kickoff)

1. **Payment providers:** confirmed Daraja (M-Pesa) + which card provider — Pesapal, Flutterwave, Stripe, DPO? Credentials ownership?
2. **Courier API:** any existing partner (Sendy, G4S, local rider network) or start with manual dispatch + Google Maps distance for fees?
3. **SMS provider:** AfricasTalking, Twilio, or Daraja SMS?
4. **Email provider:** Postmark, SES, Mailgun?
5. **Hosting target:** self-managed VPS (Forge/Ploi) or containerized (ECS/DO App Platform)?
6. **Initial branch count and warehouse layout** for seed data.
7. **Tax rules:** VAT 16% inclusive or exclusive display? ETIMS integration required for receipts?
8. **Loyalty rules:** earn rate, redemption ratio, expiry window.
9. **Image CDN budget** — R2 vs Spaces vs Cloudflare Images.
10. **Mobile apps** (§13): confirm web-only for v1, native deferred.

## 8. Phase 1 concrete task list (ready to start on sign-off)

1. `composer create-project laravel/laravel .` inside `/Users/wabwire/Dev/pos`.
2. Add Inertia, Vue 3, Tailwind, Spatie Permission, Fortify, Sanctum, Pest, Telescope.
3. Configure PG + Redis in `.env`; add `docker-compose.yml` with PG 16 + Redis + Mailpit.
4. Write `0001_identity.php` migration (users, branches, Spatie tables, audit_logs).
5. Seed 15 roles from SRDS §4.1 with starter permission matrix.
6. Build auth scaffolding (login, MFA enrol, password reset) at `/admin/login` guarded page.
7. Create admin layout Vue component with brand tokens (`#092B60`, `#079447`, `#C5A66A`), Quicksand.
8. Wire GitHub Actions: lint (Pint), static analysis (Larastan level 6), Pest, build.
9. ADR-0001: "Modular monolith over microservices".
10. ADR-0002: "PostgreSQL + cents integer for money".
11. Demo to owner: login as each of 3 roles, see role-appropriate shell.

Estimated: 2 weeks with 1 senior Laravel engineer + 1 frontend engineer.

## 9. Risks

| Risk | Mitigation |
|---|---|
| Daraja callback delays cause overselling | Reservation TTL + periodic reconciliation job; stock not deducted until webhook verified. |
| POS offline sync conflicts | Client-side UUID per sale; server rejects duplicates; conflict queue for manual resolution; never silently overwrite ledger. |
| Scope creep (15 modules, 9 phases) | Enforce Definition of Done (§15) per phase; no phase declared complete without UAT sign-off. |
| Image storage cost | Pre-upload resize + WebP conversion; aggressive HTTP cache. |
| Concurrent stock race | Row-level `FOR UPDATE` locks + Redis short-lived mutex per variant during checkout initiate. |
| Expansion into men's / kids' breaks assumptions | No category hardcoding; attribute system driven by product-type config. |

## 10. Sign-off checklist

- [ ] Stack approved (Laravel + Inertia + Vue + PG).
- [ ] Repository layout approved.
- [ ] Data model first-pass approved.
- [ ] Phase sequencing approved.
- [ ] Open questions §7 answered.
- [ ] Budget and timeline agreed.
- [ ] Phase 1 kickoff authorized.

---

*Prepared for QUEENSONS by STACKGee Technologies, 2026-11-12.*
