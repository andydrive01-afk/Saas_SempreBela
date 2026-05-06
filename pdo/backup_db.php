<?php
include_once __DIR__ . '/connection.php';

try {
    $c    = new connection();
    $pdo  = $c->connect();
} catch (Exception $e) {
    http_response_code(500);
    die('Erro ao conectar ao banco: ' . htmlspecialchars($e->getMessage()));
}

$dbname   = defined('DB_NAME') ? DB_NAME : 'database';
$filename = 'backup_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');

$header  = "-- ============================================================\n";
$header .= "-- Backup gerado pelo sistema Espaço da Beleza\n";
$header .= "-- Data: " . date('d/m/Y H:i:s') . "\n";
$header .= "-- Banco: {$dbname}\n";
$header .= "-- ============================================================\n\n";
$header .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$header .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
$header .= "SET NAMES utf8mb4;\n\n";
fwrite($out, $header);

/* ── Lista todas as tabelas ── */
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    fwrite($out, "\n-- ────────────────────────────────────────\n");
    fwrite($out, "-- Tabela: `{$table}`\n");
    fwrite($out, "-- ────────────────────────────────────────\n\n");

    /* DROP + CREATE */
    fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n");
    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
    fwrite($out, $create[1] . ";\n\n");

    /* Dados */
    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) continue;

    /* Cabeçalho do INSERT */
    $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
    fwrite($out, "INSERT INTO `{$table}` ({$cols}) VALUES\n");

    $total = count($rows);
    foreach ($rows as $i => $row) {
        $vals = array_map(function($v) use ($pdo) {
            if ($v === null) return 'NULL';
            return $pdo->quote($v);
        }, array_values($row));

        $line = '(' . implode(', ', $vals) . ')';
        $line .= ($i < $total - 1) ? ",\n" : ";\n";
        fwrite($out, $line);
    }
    fwrite($out, "\n");
}

fwrite($out, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
fwrite($out, "-- Fim do backup\n");
fclose($out);
exit;
