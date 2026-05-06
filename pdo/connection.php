<?php
class connection {
    function connect() {
        $cfg = dirname(__DIR__) . '/config.php';
        if (file_exists($cfg)) include_once $cfg;

        $driver = strtolower(defined('DB_DRIVER') ? DB_DRIVER : 'mysql');

        /* ── SQLite ── */
        if ($driver === 'sqlite') {
            $file = defined('DB_FILE') ? DB_FILE : dirname(__DIR__) . '/data/salao.sqlite';
            $dir  = dirname($file);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $pdo = new PDO("sqlite:{$file}", null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON;');
            $pdo->exec('PRAGMA journal_mode = WAL;');
            return $pdo;
        }

        /* ── PostgreSQL ── */
        if ($driver === 'pgsql') {
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $name = defined('DB_NAME') ? DB_NAME : '';
            $user = defined('DB_USER') ? DB_USER : '';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            $port = defined('DB_PORT') ? DB_PORT : 5432;
            return new PDO("pgsql:host={$host};port={$port};dbname={$name}", $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ]);
        }

        /* ── MySQL / MariaDB (default) ── */
        if (file_exists($cfg) && defined('DB_HOST')) {
            $host = DB_HOST;
            $name = defined('DB_NAME') ? DB_NAME : '';
            $user = defined('DB_USER') ? DB_USER : '';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            $port = defined('DB_PORT') ? DB_PORT : 3306;
            $dsn  = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        } else {
            // Fallback: Replit Unix socket
            $dsn  = "mysql:unix_socket=/tmp/mysql.sock;dbname=database;charset=utf8mb4";
            $user = 'root';
            $pass = '';
        }

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);
    }
}
