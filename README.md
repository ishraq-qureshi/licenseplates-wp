# LicensePlates.TV

WordPress + WooCommerce store for [licenseplates.tv](https://www.licenseplates.tv) — customers design and order
custom license plates for different US states, Canadian provinces, and countries. Plate text/font/state selection
is rendered on the fly as an image by the custom `lptv-plates` plugin (`wp-content/plugins/lptv-plates`).

## Stack

- WordPress (custom theme: `lptv`, child theme: `lptv-child`)
- WooCommerce (HPOS enabled — orders live in `wc_orders`/`wc_order_addresses`, not `wp_posts`)
- Custom plugin `lptv-plates` — plate template rendering (GD image generation), legacy Zen Cart order migration
- MariaDB
- Local dev: Docker Compose (PHP 8.2 + Apache, MariaDB, wp-cli, Adminer)

## Repo scope

This repo tracks the site's **code**: WordPress core, themes, plugins, and dev tooling. It does **not** track:

- `wp-content/uploads/` — user-uploaded media
- Large binary asset libraries inside `lptv-plates` (plate template images, fonts, decals) and `wp-content/resources`
- Database dumps (`*.sql`), backup archives (`*.zip`), and log files
- `.env` / `wp-config.php` — machine-specific secrets and DB credentials

See `.gitignore` for the full list. Media/assets/DB are synced separately (e.g. rsync from production, or a
fresh DB export) — ask whoever handed you this repo how that's currently done.

## Local setup

### Prerequisites

- Docker + Docker Compose
- A production database dump (`.sql`) — ask for the latest export
- A production `wp-content` copy (or at least `uploads/` + the `lptv-plates` asset folders) if you need real
  images/plate templates to render locally

### 1. Environment file

```bash
cp .env.example .env
```

Adjust credentials in `.env` if you want, but the defaults work out of the box with the Docker Compose file.

### 2. wp-config.php

Copy `wp-config-sample.php` to `wp-config.php` and fill in:

```php
define( 'DB_NAME', 'lptv_local' );      // matches .env MYSQL_DATABASE
define( 'DB_USER', 'lptv' );            // matches .env MYSQL_USER
define( 'DB_PASSWORD', 'lptvlocalpw' ); // matches .env MYSQL_PASSWORD
define( 'DB_HOST', 'db' );              // the docker-compose service name, not localhost
```

Also generate a fresh set of auth keys/salts from https://api.wordpress.org/secret-key/1.1/salt/.

**Known local-only tweaks** (see production's version of this file for the real settings):
- Set `WP_CACHE` to `false` — production's WP Rocket cache/`advanced-cache.php` won't match a fresh local DB
  and will cause fatals until regenerated.

### 3. Start the stack

```bash
docker compose up -d
```

This starts:
| Service    | Purpose                                  | Port |
|------------|-------------------------------------------|------|
| `db`       | MariaDB                                    | —    |
| `wordpress`| PHP 8.2 + Apache serving the site           | 8080 |
| `wpcli`    | idle container for running `wp` commands   | —    |
| `adminer`  | DB admin UI                                | 8081 |

### 4. Import the database

```bash
docker compose exec -T db sh -c 'mariadb -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < /path/to/dump.sql
```

This will take a few minutes for a production-sized dump.

### 5. Point the site at a local domain

Production URLs are baked into the DB (options, post content, serialized WooCommerce/plugin data), so a plain
`localhost:8080` won't render correctly — rewrite the domain instead:

```bash
docker compose exec wpcli wp --path=/var/www/html --allow-root \
  search-replace 'https://www.licenseplates.tv' 'http://licenseplates.local' --all-tables --precise
```

Then either:
- Access the site directly at `http://localhost:8080` (Host header will be `localhost`, some absolute-URL
  assets/links may not resolve), **or**
- Set up a real local domain (recommended): add `127.0.0.1 licenseplates.local` to `/etc/hosts` and reverse-proxy
  it to `localhost:8080` (e.g. via your host's Apache/Nginx), so the site works exactly like production.

If you use a different local domain, run the `search-replace` above with that domain instead.

### 6. Scrub real customer/user emails (important before sending any test email)

The production DB contains real customer emails. Run the scrub script to replace every email address
(users, orders, comments, WooCommerce customer data, wp_options, serialized postmeta) with a deterministic
`@yopmail.com` address, so nothing you do locally can ever email a real person:

```bash
# dry run first
docker compose exec wpcli wp --path=/var/www/html --allow-root eval-file scripts/scrub-emails.php dry-run

# then for real
docker compose exec wpcli wp --path=/var/www/html --allow-root eval-file scripts/scrub-emails.php
docker compose exec wpcli wp --path=/var/www/html --allow-root cache flush
```

### 7. Outgoing mail (test safely, don't use production SMTP)

The site ships with `smtp2go` configured for production. Locally, deactivate it and use a test SMTP catcher
(e.g. [Mailtrap](https://mailtrap.io)) instead, via **Easy WP SMTP**:

```bash
docker compose exec wpcli wp --path=/var/www/html --allow-root plugin deactivate smtp2go
docker compose exec wpcli wp --path=/var/www/html --allow-root plugin install easy-wp-smtp --activate
```

Configure Easy WP SMTP (wp-admin → Easy WP SMTP → Settings, or via `wp option update easy_wp_smtp`) with your
own test SMTP credentials. Never point local `easy_wp_smtp`/mailer settings at the production SMTP2GO account.

### 8. Log in

The login page is not at the default `/wp-login.php` — **All In One WP Security** renames it. Check
`wp-admin → All In One WP Security → Brute Force` (or ask a teammate) for the current custom login slug.

The **Cloudflare Turnstile** plugin (`simple-cloudflare-turnstile`) is domain-locked to production and will
block the login form locally — deactivate it for local dev:

```bash
docker compose exec wpcli wp --path=/var/www/html --allow-root plugin deactivate simple-cloudflare-turnstile
```

## Known quirks / gotchas

- **PHP error display**: the custom plate-image generator (`lpgenI.php`) writes raw binary PNG output. If PHP
  `display_errors` is on, any warning/notice from the (buggy but harmless) plate-rendering code gets printed
  before the image bytes and corrupts the image. Keep `display_errors = Off` / `log_errors = On` in
  `docker/wordpress/php.ini` — this matches how production is configured.
- **`.htaccess` forces HTTPS** (`## Begin SSL Redirection` block) — commented out for local HTTP-only dev.
  Don't re-enable it unless you've also set up local TLS.
- **`wp-content/plugins/lptv-plates/sync-orders.php`** is a CLI-only legacy Zen Cart → WooCommerce order sync
  script. Its production API URL is intentionally neutered in this repo (points to `disabled-locally.invalid`)
  so it can never accidentally call the live production migration API. Do not re-point it at production from a
  local/dev copy.
- **SQLite object cache** (`wp-content/object-cache.php` drop-in) persists to a `.ht.object-cache*.sqlite` file
  in `wp-content/`. If you ever see stale option values (e.g. `siteurl` not reflecting a `search-replace` you
  just ran), delete that file and run `wp cache flush`.

## Scripts

- `scripts/scrub-emails.php` — one-off, idempotent script to replace real emails with deterministic
  `@yopmail.com` addresses across the whole DB (see step 6). Safe to re-run.

## Deploying to production

This repo is code-only — coordinate with the team on how code changes here get deployed (there is no
automated CI/CD pipeline configured yet). Never point local dev tooling (SMTP, the Zen Cart sync script, DB
credentials) at production systems.
