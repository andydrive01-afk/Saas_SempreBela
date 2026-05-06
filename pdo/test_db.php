<?php
header('Content-Type: application/json; charset=utf-8');

$driver = strtolower(trim($_POST['db_driver'] ?? 'mysql'));
$host   = trim($_POST['db_host']   ?? 'localhost');
$name   = trim($_POST['db_name']   ?? '');
$user   = trim($_POST['db_user']   ?? '');
$pass   = trim($_POST['db_pass']   ?? '');
$port   = (int)($_POST['db_port']  ?? 3306);
$file   = trim($_POST['db_file']   ?? '');

try {
    if ($driver === 'sqlite') {
        if (!$file) $file = dirname(__DIR__) . '/data/salao.sqlite';
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!is_writable($dir)) throw new Exception("Diretório não gravável: {$dir}");
        $pdo = new PDO("sqlite:{$file}", null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->query("SELECT 1");
        echo json_encode(['ok' => true, 'msg' => "✅ SQLite acessível em: {$file}"]);

    } elseif ($driver === 'pgsql') {
        if (!$name || !$user) throw new Exception("Nome do banco e usuário são obrigatórios.");
        $port = $port ?: 5432;
        $pdo  = new PDO("pgsql:host={$host};port={$port};dbname={$name}", $user, $pass,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->query("SELECT 1");
        echo json_encode(['ok' => true, 'msg' => "✅ PostgreSQL conectado em {$host}:{$port}/{$name}"]);

    } else {
        // MySQL / MariaDB
        if (!$name || !$user) throw new Exception("Nome do banco e usuário são obrigatórios.");
        $port = $port ?: 3306;
        $pdo  = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                        $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $ver  = $pdo->query("SELECT VERSION()")->fetchColumn();
        echo json_encode(['ok' => true, 'msg' => "✅ Conectado com sucesso! Versão: {$ver}"]);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => '❌ Falha: ' . $e->getMessage()]);
}
exit;
