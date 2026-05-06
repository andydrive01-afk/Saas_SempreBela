<?php
/* ── Redireciona para setup se config.php não existir (hospedagem compartilhada) ── */
$_cfg_path = dirname(__DIR__) . '/config.php';
$_current  = basename($_SERVER['PHP_SELF'] ?? '');
if (!file_exists($_cfg_path) && !in_array($_current, ['setup.php','pdo/save_settings.php','pdo/test_db.php','pdo/install_db.php'])) {
    header('Location: /setup.php?first_run=1');
    exit;
}

if (!isset($conn)) {
    include_once __DIR__ . '/../pdo/connection.php';
    try {
        $_sc  = new connection();
        $conn = $_sc->connect();
    } catch (Exception $_e) {
        // Banco não acessível — redireciona para setup
        if (!in_array($_current, ['setup.php'])) {
            header('Location: /setup.php?db_error=1');
            exit;
        }
    }
}

if (isset($conn)) {
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
