<?php
session_start();

include_once __DIR__ . '/connection.php';
include_once __DIR__ . '/DAO/user_DAO.php';

$login    = trim($_POST['login']    ?? '');
$senha    = trim($_POST['senha']    ?? '');
$redirect = trim($_POST['redirect'] ?? 'index.php');

// Basic redirect safety
if (!preg_match('/^[a-zA-Z0-9\/_\-\.?=&]+$/', $redirect)) {
    $redirect = 'index.php';
}

try {
    $c    = new connection();
    $pdo  = $c->connect();
    $dao  = new user_DAO();

    $user = $dao->find_by_login($login, $pdo);

    if (!$user || !password_verify($senha, $user->senha_hash)) {
        header('Location: ../login.php?erro=credenciais&redirect=' . urlencode($redirect));
        exit;
    }

    if (!$user->ativo) {
        header('Location: ../login.php?erro=inativo&redirect=' . urlencode($redirect));
        exit;
    }

    $_SESSION['user_id'] = $user->id;
    $_SESSION['login']   = $user->login;
    $_SESSION['nome']    = $user->nome;
    $_SESSION['nivel']   = $user->nivel;

    header('Location: ../' . ltrim($redirect, '/'));
    exit;

} catch (Exception $e) {
    header('Location: ../login.php?erro=' . urlencode('Erro interno: ' . $e->getMessage()));
    exit;
}
