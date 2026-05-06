# Espaço da Beleza — Beauty Salon Management System

## Run & Operate
- **Start:** `bash start.sh` (starts MySQL 8.0 + PHP server on port 5000)
- **First visit:** wizard automático em `/setup.php` (4 steps: DB → Admin → Master → Identidade)
- **Login:** `/login.php`
- **Required env vars:** none (MySQL runs locally via Unix socket)

## Stack
- PHP 8.2 (built-in server, port 5000)
- MySQL 8.0 — socket `/tmp/mysql.sock`, DB `database`, user `root` (no password)
- Multi-driver PDO: MySQL/MariaDB, SQLite, PostgreSQL
- jQuery 3.5 + Select2 + Lord Icons (vendor files in `js/`)
- Plain CSS (`css/main.css` — CRLF line endings, append-only via bash)

## Where Things Live
- `setup.php` — wizard (first run) + runtime config (admin only)
- `login.php` — login page (any user level)
- `pdo/do_login.php`, `pdo/do_logout.php` — auth actions
- `pdo/wizard_action.php` — handles all wizard step POST submissions
- `inc/auth.php` — session + access level middleware (included at top of every page)
- `inc/settings.php` — loads salon settings from DB (included after auth.php)
- `pdo/connection.php` — multi-driver PDO factory (reads config.php constants)
- `pdo/DAO/user_DAO.php` — user CRUD (create, find, list, delete, change_password)
- `pdo/DAO/settings_DAO.php` — settings get_all/set with multi-driver upsert
- `css/theme.php` — dynamic CSS from DB colors
- `config.php` — gitignored; contains DB_DRIVER, DB_HOST, DB_NAME, DB_USER, DB_PASS + SETUP_COMPLETE flag
- `config.sample.php` — template for shared hosting
- `sql/database.sql` — base schema (does not include `usuarios` table — created by wizard/start.sh)

## Architecture Decisions
- **Wizard-first flow:** if `SETUP_COMPLETE` is not defined in config.php, ALL pages redirect to `/setup.php`. After wizard, flag is appended to config.php.
- **3-level auth:** Admin > Master > Agente — stored in `usuarios` table with bcrypt passwords. Sessions are PHP file-based (works without DB for auth check).
- **Multi-driver PDO:** connection.php reads `DB_DRIVER` ('mysql'/'sqlite'/'pgsql') from config.php. install_db.php generates driver-appropriate SQL for all 3 drivers.
- **settings_DAO.set()** detects PDO driver at runtime and uses the correct upsert syntax (ON DUPLICATE KEY / INSERT OR REPLACE / ON CONFLICT).
- **Access control:** pages set `$_auth_nivel = 'master'` before including auth.php. pdo/ action files use inline session check + nivel array. `can($nivel)` helper available globally.
- Settings stored as key-value rows in `configuracoes` table; loaded via `inc/settings.php` on every page.
- `atendente_id` column added to `atendimentos` via idempotent migration in `start.sh`.
- `start.sh` also auto-creates config.php and `usuarios` table for Replit environment.

## Product
- Register and manage clients, services, products (inventory), and attendants
- Book appointments with date picker, attendant selection, and auto-calculated total
- Track weekly financials with goals, cash in/out, and notifications
- Monthly revenue summary with per-month breakdown
- Per-client history page with spend stats and full appointment list
- Live search bars on all list pages; filter by client/attendant/date on treatments
- Block deletion of services/products used in existing appointments
- **Setup wizard** (4 steps): DB config, Admin account, Master account, Salon identity
- **Login system** with 3 levels: Admin (full access + setup), Master (manage data + agents), Agente (appointments only)
- **Backup** (SQL dump via PHP) inside Configurações — no phpMyAdmin needed

## User Preferences
- Language: Portuguese (pt-BR) throughout the UI
- Color theme: dark maroon primary + purple accent (configurable via setup)
- Append CSS only via bash (CRLF issue in main.css)

## Gotchas
- `css/main.css` has CRLF line endings — never use the edit tool on it; always `cat >> css/main.css`
- MySQL binary: `/nix/store/s2lbn1axpc79kwnc829k5idkwabfq459-mysql-8.0.42/bin/`
- MySQL data dir: `/home/runner/mysql8-data/`
- `inc/settings.php` uses `$_sc` / `$_sdao` / `$_settings` to avoid variable name collisions
- On Replit: config.php is created by start.sh WITHOUT `SETUP_COMPLETE`, so wizard runs on first visit
- Pages starting with `<!DOCTYPE html>` (costumers.php, services.php, new_*.php) have auth.php prepended as `<?php ... ?>` before DOCTYPE
- SQLite file stored in `data/salao.sqlite` (auto-created by connection.php if missing)
