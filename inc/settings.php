<?php
if (!isset($conn)) {
    include_once __DIR__ . '/../pdo/connection.php';
    $_sc   = new connection();
    $conn  = $_sc->connect();
}
include_once __DIR__ . '/../pdo/DAO/settings_DAO.php';
$_sdao     = new settings_DAO();
$_settings = $_sdao->get_all($conn);

$salon_name   = $_settings['nome_salao']   ?? 'Espaço da Beleza Lucia Reis';
$salon_local  = $_settings['local_salao']  ?? 'Santana do Livramento, RS';
$salon_logo   = $_settings['logo_path']    ?? 'img/logo-full.png';
$salon_icon   = $_settings['icon_path']    ?? 'img/logo.png';
$cor_primaria = $_settings['cor_primaria'] ?? '#7a3444';
$cor_destaque = $_settings['cor_destaque'] ?? '#b06ab3';
