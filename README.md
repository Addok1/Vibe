# Ride-Hailing / Transport Admin & Web Booking

Laravel application providing an **admin panel**, **mobile API**, and **customer web booking** for a transport/ride-hailing product. Roles include admin, owner, dispatcher, agent, franchise, and driver; features include zones, pricing, promos, payments (Stripe, PayPal, Razorpay, Cashfree, Mercado Pago), support tickets, and optional Firebase (push, OTP, real-time).

## What this app does

- **Admin panel (Inertia + Vue 3):** Dashboard, users, drivers, owners, fleet, zones, map settings, ride requests, ongoing/scheduled rides, payments, promos, subscriptions, support, notifications, and general/third-party settings.
- **Mobile API:** REST API for user and driver apps (auth, trip lifecycle, payments, etc.).
- **Web booking:** Customer-facing flow to create a booking (instant or scheduled), with login (OTP/password), history, and profile.
- **Install wizard:** Optional first-run setup at `/` (when install routes are active) for verification and initial configuration.

## Requirements

- **PHP** 8.2+ (Laravel 12)
- **Node.js** 18+ (Vite, Vue 3, frontend build)
- **Database:** MySQL/MariaDB (or SQLite for local dev)
- **Composer** and **npm** / **yarn**

## Quick start

1. **Clone and install**
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Configure environment**  
   Edit `.env`: set `APP_URL`, database (`DB_*`), and any optional services (mail, queue, Redis, Stripe, Firebase, SMS, etc.). See [.env.example](.env.example) for all keys and short comments.

3. **Database**
   ```bash
   php artisan migrate
   # Optional: php artisan db:seed
   ```

4. **Frontend**
   ```bash
   npm install
   npm run build
   # or for dev: npm run dev
   ```

5. **Run**
   ```bash
   php artisan serve
   ```
   Visit `APP_URL` (e.g. `http://localhost:8000`). If install wizard is enabled, go to `/`; otherwise use your configured login URLs (e.g. `/login/user` for customer, `/login/admin` for admin).

## Queues and scheduler

- **Queues:** Notifications, jobs, and heavy work should use a queue. Set `QUEUE_CONNECTION=database` (or `redis`) and run:
  ```bash
  php artisan queue:work
  ```

- **Scheduler:** The app relies on scheduled commands for cancelling expired requests, assigning drivers for regular/scheduled rides, document expiry notifications, subscription expiry, OTP cleanup, and promo deactivation. **You must add a cron entry** so Laravel’s scheduler runs every minute:
  ```bash
  * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
  ```
  See [docs/SCHEDULER_AND_CRON.md](docs/SCHEDULER_AND_CRON.md) for the full list of commands and schedule.

## Env and docs

- **[.env.example](.env.example)** – All environment keys the app uses, with placeholder values and short comments (no secrets).
- **[docs/SCHEDULER_AND_CRON.md](docs/SCHEDULER_AND_CRON.md)** – Cron setup and list of scheduled commands.
- **[docs/FRONTEND_CONVENTIONS.md](docs/FRONTEND_CONVENTIONS.md)** – Vue 3 / Inertia conventions (Composition API, state, i18n, accessibility).
- **[IMPROVEMENTS_AND_FEATURES_PLAN.md](IMPROVEMENTS_AND_FEATURES_PLAN.md)** – Product/tech improvement plan and phased priorities.

For business rules and internal processes, see LaRecipe or your internal docs if configured.

## Social login (Google/Facebook)

This repo supports **User role** social login/signup for:
- **Mobile API token login**: `POST /api/v1/user/social/{provider}` where `{provider}` is `google` or `facebook`.
  - Send `id_token` (Google) or `access_token` (Google/Facebook).
  - If the user doesn't exist yet, also send `mobile` (and optionally `country`) to create the account.
  - Response matches existing mobile login format: `{ success, message, access_token }`.
- **Web booking OAuth redirect flow**:
  - `GET /social/{provider}/redirect?redirect_to=/create-booking`
  - `GET /social/{provider}/callback`
  - If a new account requires mobile, callback redirects to `/login?social_signup=1` and the frontend can call `POST /social/complete` with `mobile` to finish.
- **Mobile fallback via web redirect** (when the app can't do native Google/Facebook):
  - `GET /social/{provider}/redirect?redirect_uri=myapp://auth/callback`
  - On success, callback redirects to the `redirect_uri` with `access_token=...` (only if `MOBILE_SOCIAL_REDIRECT_ALLOWLIST` allows it).

Required env keys (see `.env.example`):
`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET`, and `MOBILE_SOCIAL_REDIRECT_ALLOWLIST` (optional, for deep links).

## License

Same as the base Laravel framework (MIT unless otherwise stated).
