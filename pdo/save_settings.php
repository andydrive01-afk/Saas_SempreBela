<?php
/* ─── Salva configurações do banco (config.php) e do sistema (BD) ─── */

/* ── 1. Config do Banco (escrita em config.php) ── */
$db_fields = ['db_host','db_name','db_user','db_pass','db_port'];
$saving_db = false;
foreach ($db_fields as $f) {
    if (isset($_POST[$f]) && $_POST[$f] !== '') { $saving_db = true; break; }
}

if ($saving_db) {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = trim($_POST['db_pass'] ?? '');
    $db_port = (int)($_POST['db_port'] ?? 3306);

    if (!$db_name || !$db_user) {
        $msg = urlencode('Preencha o nome do banco e o usuário.');
        header('Location: ../setup.php?error=' . $msg);
        exit;
    }

    $cfg  = "<?php\n";
    $cfg .= "// Gerado automaticamente pelo setup em " . date('Y-m-d H:i:s') . "\n";
    $cfg .= "define('DB_HOST',    " . var_export($db_host, true) . ");\n";
    $cfg .= "define('DB_NAME',    " . var_export($db_name, true) . ");\n";
    $cfg .= "define('DB_USER',    " . var_export($db_user, true) . ");\n";
    $cfg .= "define('DB_PASS',    " . var_export($db_pass, true) . ");\n";
    $cfg .= "define('DB_PORT',    " . var_export($db_port, true) . ");\n";
    $cfg .= "define('DB_CHARSET', 'utf8mb4');\n";

    $dest = dirname(__DIR__) . '/config.php';
    if (file_put_contents($dest, $cfg) === false) {
        $msg = urlencode('Não foi possível gravar config.php. Verifique as permissões da pasta.');
        header('Location: ../setup.php?error=' . $msg);
        exit;
    }
}

/* ── 2. Configurações do sistema (salvas no banco) ── */
$errors = [];

try {
    include_once __DIR__ . '/connection.php';
    include_once __DIR__ . '/DAO/settings_DAO.php';

    $c    = new connection();
    $conn = $c->connect();
    $dao  = new settings_DAO();

    $nome  = trim($_POST['nome_salao']   ?? '');
    $local = trim($_POST['local_salao']  ?? '');
    $cor_p = trim($_POST['cor_primaria'] ?? '');
    $cor_a = trim($_POST['cor_destaque'] ?? '');

    // Cria tabela configuracoes se ainda não existir
    $conn->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        chave VARCHAR(80) NOT NULL PRIMARY KEY,
        valor TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
            $dest = dirname(__DIR__) . '/img/salon_logo.' . $ext;
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
} catch (Exception $e) {
    // Se o banco ainda não está configurado, apenas salva config.php e redireciona
    if ($saving_db) {
        header('Location: ../setup.php?saved=1&db=1');
        exit;
    }
    $errors[] = 'Erro no banco de dados: ' . $e->getMessage();
}

if (empty($errors)) {
    header('Location: ../setup.php?saved=1');
} else {
    $msg = urlencode(implode(' | ', $errors));
    header('Location: ../setup.php?error=' . $msg);
}
exit;
