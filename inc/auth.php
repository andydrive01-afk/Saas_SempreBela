<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/* ── Páginas públicas (não precisam de login) ── */
$_public = [
    'login.php','setup.php',
    'do_login.php','do_logout.php',
    'test_db.php','install_db.php','save_settings.php',
    'backup_db.php',
];
$_current = basename($_SERVER['PHP_SELF'] ?? '');
$_is_public = in_array($_current, $_public);

/* ── Verifica se o setup foi concluído ── */
$_cfg = dirname(__DIR__) . '/config.php';
if (file_exists($_cfg)) include_once $_cfg;
$_setup_done = defined('SETUP_COMPLETE') && SETUP_COMPLETE;

if (!$_setup_done && !$_is_public) {
    header('Location: /setup.php');
    exit;
}

/* ── Verifica login ── */
if (!$_is_public && !isset($_SESSION['user_id'])) {
    $redir = urlencode($_SERVER['REQUEST_URI'] ?? '/index.php');
    header('Location: /login.php?redirect=' . $redir);
    exit;
}

/* ── Verifica nível de acesso ──
   A página pode definir $_auth_nivel = 'master' ou 'admin' antes do include */
if (isset($_SESSION['nivel']) && isset($_auth_nivel)) {
    $niveis = ['agente' => 1, 'master' => 2, 'admin' => 3];
    $u_lvl  = $niveis[$_SESSION['nivel']] ?? 0;
    $r_lvl  = $niveis[$_auth_nivel]       ?? 0;
    if ($u_lvl < $r_lvl) {
        header('Location: /login.php?erro=sem_permissao');
        exit;
    }
}

/* ── Helper: retorna true se o usuário tem nível suficiente ── */
function can(string $nivel): bool {
    $niveis = ['agente' => 1, 'master' => 2, 'admin' => 3];
    $u = $niveis[$_SESSION['nivel'] ?? 'agente'] ?? 0;
    $r = $niveis[$nivel] ?? 0;
    return $u >= $r;
}
