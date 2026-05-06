<?php
include_once __DIR__ . '/connection.php';
include_once __DIR__ . '/DAO/settings_DAO.php';

$c    = new connection();
$conn = $c->connect();
$dao  = new settings_DAO();

$errors = [];

$nome  = trim($_POST['nome_salao']   ?? '');
$local = trim($_POST['local_salao']  ?? '');
$cor_p = trim($_POST['cor_primaria'] ?? '#7a3444');
$cor_a = trim($_POST['cor_destaque'] ?? '#b06ab3');

if ($nome  !== '') $dao->set('nome_salao',   $nome,  $conn);
if ($local !== '') $dao->set('local_salao',  $local, $conn);
if ($cor_p !== '') $dao->set('cor_primaria', $cor_p, $conn);
if ($cor_a !== '') $dao->set('cor_destaque', $cor_a, $conn);

/* ── Logo upload ── */
if (!empty($_FILES['logo']['name'])) {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
    $mime    = mime_content_type($_FILES['logo']['tmp_name']);
    if (in_array($mime, $allowed)) {
        $ext  = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $dest = __DIR__ . '/../img/salon_logo.' . $ext;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
            $path = 'img/salon_logo.' . $ext;
            $dao->set('logo_path', $path, $conn);
            $dao->set('icon_path', $path, $conn);
        } else {
            $errors[] = 'Erro ao salvar o arquivo de logo.';
        }
    } else {
        $errors[] = 'Formato de imagem inválido. Use JPG, PNG, GIF, WebP ou SVG.';
    }
}

if (empty($errors)) {
    header('Location: ../setup.php?saved=1');
} else {
    $msg = urlencode(implode(' | ', $errors));
    header('Location: ../setup.php?error=' . $msg);
}
exit;
