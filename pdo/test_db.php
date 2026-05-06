<?php
header('Content-Type: application/json; charset=utf-8');

$host    = trim($_POST['db_host']    ?? 'localhost');
$name    = trim($_POST['db_name']    ?? '');
$user    = trim($_POST['db_user']    ?? '');
$pass    = trim($_POST['db_pass']    ?? '');
$port    = (int)($_POST['db_port']   ?? 3306);
$charset = 'utf8mb4';

if (!$name || !$user) {
    echo json_encode(['ok' => false, 'msg' => 'Preencha o nome do banco e o usuário.']);
    exit;
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo json_encode(['ok' => true, 'msg' => 'Conexão bem-sucedida! Banco de dados acessível.']);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Falha na conexão: ' . $e->getMessage()]);
}
exit;
