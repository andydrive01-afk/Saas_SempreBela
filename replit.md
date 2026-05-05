# Espaço da Beleza - Beauty Salon Management System

## Overview
A PHP web application for a beauty salon (Espaço da Beleza Lucia Reis) to manage clients, services, appointments, inventory, and financial data.

## Architecture
- **Backend:** PHP 8.2 with PDO for database access
- **Database:** MySQL 8.0 (started via startup script)
- **Frontend:** Plain HTML/CSS/JS with jQuery and Select2
- **Server:** PHP built-in server on port 5000

## Project Structure
- `index.php` — Main landing page with navigation
- `costumers.php` — Client/customer management
- `services.php` — Service catalog management
- `treatments.php` — Appointment/treatment management
- `products.php` — Inventory/stock management
- `financial_data.php` — Financial tracking and weekly goals
- `new_*.php` — Forms to create new records
- `pdo/` — Database layer:
  - `connection.php` — PDO MySQL connection via Unix socket
  - `classes/` — Domain model classes
  - `DAO/` — Data access objects (CRUD operations)
  - `*.php` — Action scripts (add, edit, delete operations)
- `css/` — Stylesheets
- `js/` — JavaScript vendor files (jQuery, Select2, lord-icon)
- `img/` — Images
- `sql/database.sql` — Database schema dump

## Database Setup
- MySQL 8.0 binary: `/nix/store/s2lbn1axpc79kwnc829k5idkwabfq459-mysql-8.0.42/bin/`
- Data directory: `/home/runner/mysql8-data/`
- Socket: `/tmp/mysql.sock`
- Database name: `database`
- User: `root` (no password)
- Connection: `mysql:unix_socket=/tmp/mysql.sock;dbname=database`

## Startup
The `start.sh` script:
1. Starts MySQL 8.0 in the background
2. Waits until MySQL is ready
3. Starts PHP built-in server on `0.0.0.0:5000`

## Workflow
- **Start application** — runs `bash start.sh`, serves on port 5000

## Deployment
- Target: VM (always running, needed for MySQL + PHP)
- Run command: `bash start.sh`
