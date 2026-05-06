<?php
session_start();

// Already logged in?
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Try to load branding
$salon_name   = 'Sistema de Beleza';
$cor_primaria = '#7a3444';
$cor_destaque = '#b06ab3';
$salon_logo   = 'img/logo-full.png';
$salon_icon   = 'img/logo.png';

$cfg = __DIR__ . '/config.php';
if (file_exists($cfg)) {
    include_once $cfg;
    if (defined('SETUP_COMPLETE') && SETUP_COMPLETE) {
        try {
            include_once 'pdo/connection.php';
            include_once 'pdo/DAO/settings_DAO.php';
            $_c   = new connection();
            $_pdo = $_c->connect();
            $_dao = new settings_DAO();
            $_s   = $_dao->get_all($_pdo);
            $salon_name   = $_s['nome_salao']   ?? $salon_name;
            $cor_primaria = $_s['cor_primaria'] ?? $cor_primaria;
            $cor_destaque = $_s['cor_destaque'] ?? $cor_destaque;
            $salon_logo   = $_s['logo_path']    ?? $salon_logo;
            $salon_icon   = $_s['icon_path']    ?? $salon_icon;
        } catch (Exception $e) {}
    } else {
        // Setup not done → redirect to wizard
        header('Location: setup.php');
        exit;
    }
}

$error    = $_GET['erro'] ?? '';
$redirect = $_GET['redirect'] ?? 'index.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/fonts.css" rel="stylesheet">
    <link rel="icon" href="<?=$salon_icon?>">
    <title><?=htmlspecialchars($salon_name)?> — Login</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, <?=$cor_primaria?>, <?=$cor_destaque?>);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Roboto', sans-serif;
        }
        .login-box {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 40px rgba(0,0,0,.22);
            width: 380px;
            padding: 40px 36px 36px;
            display: flex; flex-direction: column; align-items: center; gap: 0;
        }
        .login-logo {
            max-width: 120px;
            max-height: 90px;
            object-fit: contain;
            margin-bottom: 18px;
            border-radius: 8px;
        }
        .login-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 6px;
            text-align: center;
        }
        .login-sub {
            font-size: 13px;
            color: #999;
            margin-bottom: 28px;
            text-align: center;
        }
        .login-field { width: 100%; margin-bottom: 16px; }
        .login-field label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #666;
            margin-bottom: 6px;
        }
        .login-field input {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid #ddd; border-radius: 9px;
            font-size: 15px; outline: none; transition: border-color .2s;
            background: #fafafa;
        }
        .login-field input:focus { border-color: <?=$cor_primaria?>; background: #fff; }
        .pass-wrap { position: relative; }
        .pass-wrap input { padding-right: 42px; }
        .pass-eye { position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            cursor: pointer; font-size: 17px; color: #bbb; user-select: none; }
        .login-btn {
            width: 100%; padding: 13px;
            border: none; border-radius: 10px;
            background: linear-gradient(135deg, <?=$cor_primaria?>, <?=$cor_destaque?>);
            color: #fff; font-size: 16px; font-weight: 700;
            cursor: pointer; margin-top: 6px;
            transition: opacity .2s, transform .1s;
        }
        .login-btn:hover { opacity: .9; transform: translateY(-1px); }
        .login-error {
            width: 100%; margin-bottom: 16px;
            padding: 10px 14px; border-radius: 8px;
            background: #fdf0f0; color: #b91c1c;
            border: 1.5px solid #f5b8b8;
            font-size: 13px; font-weight: 600;
        }
        .login-footer { margin-top: 24px; font-size: 12px; color: #ccc; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <img class="login-logo" src="<?=$salon_logo?>" alt="<?=htmlspecialchars($salon_name)?>">
        <div class="login-title"><?=htmlspecialchars($salon_name)?></div>
        <div class="login-sub">Entre com suas credenciais para acessar</div>

        <?php if ($error): ?>
        <div class="login-error">
            <?php
            $msgs = [
                'credenciais'  => '❌ Usuário ou senha incorretos.',
                'inativo'      => '⚠️ Sua conta está inativa. Contate o administrador.',
                'sem_permissao'=> '🔒 Você não tem permissão para acessar esta página.',
            ];
            echo $msgs[$error] ?? htmlspecialchars(urldecode($error));
            ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="pdo/do_login.php" style="width:100%">
            <input type="hidden" name="redirect" value="<?=htmlspecialchars($redirect)?>">
            <div class="login-field">
                <label>Usuário</label>
                <input type="text" name="login" required autofocus autocomplete="username"
                       placeholder="Seu login">
            </div>
            <div class="login-field">
                <label>Senha</label>
                <div class="pass-wrap">
                    <input type="password" name="senha" id="senha" required
                           autocomplete="current-password" placeholder="Sua senha">
                    <span class="pass-eye" onclick="var i=document.getElementById('senha');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'👁':'🙈'">👁</span>
                </div>
            </div>
            <button type="submit" class="login-btn">Entrar</button>
        </form>
        <div class="login-footer">Admin · Master · Agente</div>
    </div>
</body>
</html>
