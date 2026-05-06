<?php
class connection {
    protected $pdo;

    function connect() {
        $cfg = dirname(__DIR__) . '/config.php';

        if (file_exists($cfg)) {
            include_once $cfg;
            $host    = defined('DB_HOST')    ? DB_HOST    : 'localhost';
            $dbname  = defined('DB_NAME')    ? DB_NAME    : 'database';
            $user    = defined('DB_USER')    ? DB_USER    : 'root';
            $pass    = defined('DB_PASS')    ? DB_PASS    : '';
            $port    = defined('DB_PORT')    ? DB_PORT    : 3306;
            $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        } else {
            // Fallback: ambiente Replit (Unix socket)
            $dsn  = "mysql:unix_socket=/tmp/mysql.sock;dbname=database;charset=utf8mb4";
            $user = 'root';
            $pass = '';
        }

        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);
        return $this->pdo;
    }
}
