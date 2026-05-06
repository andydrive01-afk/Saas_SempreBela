<?php
/* ── Carrega configurações do salão ─────────────────────────────────────────
   Não faz redirecionamentos — isso é responsabilidade do inc/auth.php.
   Sempre incluir APÓS inc/auth.php.
─────────────────────────────────────────────────────────────────────────── */

if (!isset($conn)) {
    include_once __DIR__ . '/../pdo/connection.php';
    try {
        $_sc  = new connection();
        $conn = $_sc->connect();
    } catch (Exception $_e) {
        $conn = null;
    }
}

if ($conn) {
    include_once __DIR__ . '/../pdo/DAO/settings_DAO.php';
    $_sdao     = new settings_DAO();
    $_settings = $_sdao->get_all($conn);
} else {
    $_settings = [];
}

$salon_name   = $_settings['nome_salao']   ?? 'Espaço da Beleza';
$salon_local  = $_settings['local_salao']  ?? '';
$salon_logo   = $_settings['logo_path']    ?? 'img/logo-full.png';
$salon_icon   = $_settings['icon_path']    ?? 'img/logo.png';
$cor_primaria = $_settings['cor_primaria'] ?? '#7a3444';
$cor_destaque = $_settings['cor_destaque'] ?? '#b06ab3';
