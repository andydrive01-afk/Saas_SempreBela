# Espaço da Beleza - Beauty Salon Management System

## Overview
A PHP web application for a beauty salon to manage clients, services, appointments, inventory, attendants, and financial data — with full branding customization via a setup wizard.

## Run & Operate
- **Start:** `bash start.sh` (starts MySQL 8.0 + PHP server on port 5000)
- **Setup/branding:** visit `/setup.php`
- **Required env vars:** none (MySQL runs locally via Unix socket)

## Stack
- PHP 8.2 (built-in server, port 5000)
- MySQL 8.0 — socket `/tmp/mysql.sock`, DB `database`, user `root` (no password)
- jQuery 3.5 + Select2 + Lord Icons (vendor files in `js/`)
- Plain CSS (`css/main.css` — CRLF line endings, append-only via bash)

## Where Things Live
- `index.php` — home/dashboard with menu and notifications
- `setup.php` — branding wizard (name, location, logo, colors)
- `costumers.php / services.php / products.php / treatments.php / attendants.php` — list pages
- `financial_data.php` — weekly cash tracking
- `monthly_summary.php` — monthly revenue/count summary
- `client_history.php?id=N` — per-client appointment history
- `new_*.php` — create-record forms
- `pdo/` — DB layer: `connection.php`, `classes/`, `DAO/`, action scripts
- `inc/settings.php` — settings loader (included in every page)
- `css/theme.php` — dynamic stylesheet that applies saved brand colors
- `css/main.css` — base styles (CRLF endings — use bash `cat >>` to append)
- `sql/database.sql` — base schema

## Architecture Decisions
- Settings stored as key-value rows in `configuracoes` table; loaded via `inc/settings.php` on every page
- `css/theme.php` is a PHP file served as `text/css` that reads colors from DB and generates CSS overrides — linked after `main.css` on every page
- Logo uploaded via setup form → saved as `img/salon_logo.<ext>` → path stored in `configuracoes`
- `atendente_id` column added to `atendimentos` via idempotent migration in `start.sh` (checks `SHOW COLUMNS` before ALTER)
- `start.sh` runs all migrations on every boot (CREATE TABLE IF NOT EXISTS + column check)

## Product
- Register and manage clients, services, products (inventory), and attendants
- Book appointments with date picker, attendant selection, and auto-calculated total
- Track weekly financials with goals, cash in/out, and notifications
- Monthly revenue summary with per-month breakdown
- Per-client history page with spend stats and full appointment list
- Live search bars on all list pages; filter by client/attendant/date on treatments
- Block deletion of services/products used in existing appointments
- **Setup wizard** to customize salon name, location, logo, and theme colors

## User Preferences
- Language: Portuguese (pt-BR) throughout the UI
- Color theme: dark maroon primary + purple accent (configurable via setup)
- Append CSS only via bash (CRLF issue in main.css)

## Gotchas
- `css/main.css` has CRLF line endings — never use the edit tool on it; always `cat >> css/main.css`
- MySQL binary: `/nix/store/s2lbn1axpc79kwnc829k5idkwabfq459-mysql-8.0.42/bin/`
- MySQL data dir: `/home/runner/mysql8-data/` — if missing, `start.sh` re-initializes
- `inc/settings.php` uses `$_sc` / `$_sdao` / `$_settings` to avoid variable name collisions

## Pointers
- DB schema: `sql/database.sql`
- Settings DAO: `pdo/DAO/settings_DAO.php`
- Theme CSS: `css/theme.php`
