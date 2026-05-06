<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

$action = $_POST['_action'] ?? '';

/* ── Helpers ── */
function redir($url) { header('Location: ' . $url); exit; }
function flash($type, $msg) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }

/* ── Evita reenvio acidental ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redir('../setup.php');

/* ══════════════════════════════════════════════════════════
   STEP 1 — Salva config.php com driver + credenciais
══════════════════════════════════════════════════════════ */
if ($action === 'save_db') {
    $driver = strtolower(trim($_POST['db_driver'] ?? 'mysql'));
    $host   = trim($_POST['db_host'] ?? 'localhost');
    $name   = trim($_POST['db_name'] ?? '');
    $user   = trim($_POST['db_user'] ?? '');
    $pass   = trim($_POST['db_pass'] ?? '');
    $port   = (int)($_POST['db_port'] ?? 3306);
    $file   = trim($_POST['db_file'] ?? '');

    // Validação mínima
    if ($driver !== 'sqlite' && (!$name || !$user)) {
        flash('error', 'Nome do banco de dados e usuário são obrigatórios.');
        redir('../setup.php?step=1');
    }

    // Monta arquivo config.php
    if ($driver === 'sqlite') {
        if (!$file) $file = dirname(__DIR__) . '/data/salao.sqlite';
        $content = "<?php\ndefine('DB_DRIVER', 'sqlite');\ndefine('DB_FILE',   " . var_export($file, true) . ");\n";
    } elseif ($driver === 'pgsql') {
        $content  = "<?php\n";
        $content .= "define('DB_DRIVER',  'pgsql');\n";
        $content .= "define('DB_HOST',    " . var_export($host, true) . ");\n";
        $content .= "define('DB_PORT',    " . (int)($port ?: 5432) . ");\n";
        $content .= "define('DB_NAME',    " . var_export($name, true) . ");\n";
        $content .= "define('DB_USER',    " . var_export($user, true) . ");\n";
        $content .= "define('DB_PASS',    " . var_export($pass, true) . ");\n";
    } else {
        $content  = "<?php\n";
        $content .= "define('DB_DRIVER',  'mysql');\n";
        $content .= "define('DB_HOST',    " . var_export($host, true) . ");\n";
        $content .= "define('DB_PORT',    " . (int)($port ?: 3306) . ");\n";
        $content .= "define('DB_NAME',    " . var_export($name, true) . ");\n";
        $content .= "define('DB_USER',    " . var_export($user, true) . ");\n";
        $content .= "define('DB_PASS',    " . var_export($pass, true) . ");\n";
        $content .= "define('DB_CHARSET', 'utf8mb4');\n";
    }

    $cfg = dirname(__DIR__) . '/config.php';
    if (!file_put_contents($cfg, $content)) {
        flash('error', 'Não foi possível gravar config.php. Verifique as permissões do diretório.');
        redir('../setup.php?step=1');
    }

    // Testa conexão
    try {
        include_once __DIR__ . '/connection.php';
        // Re-include para pegar as novas constantes
        include_once $cfg;
        $c   = new connection();
        $pdo = $c->connect();
        $_SESSION['wizard']['db'] = true;
        redir('../setup.php?step=2');
    } catch (Exception $e) {
        unlink($cfg);
        flash('error', 'Credenciais salvas mas conexão falhou: ' . $e->getMessage());
        redir('../setup.php?step=1');
    }
}

/* ══════════════════════════════════════════════════════════
   STEP 2 — Cria conta Admin
══════════════════════════════════════════════════════════ */
if ($action === 'save_admin') {
    if (empty($_SESSION['wizard']['db'])) redir('../setup.php?step=1');

    $nome    = trim($_POST['nome']    ?? '');
    $login   = trim($_POST['login']   ?? '');
    $senha   = trim($_POST['senha']   ?? '');
    $confirm = trim($_POST['confirmar'] ?? '');

    if (!$nome || !$login || !$senha) {
        flash('error', 'Preencha todos os campos da conta Admin.');
        redir('../setup.php?step=2');
    }
    if ($senha !== $confirm) {
        flash('error', 'As senhas não coincidem.');
        redir('../setup.php?step=2');
    }
    if (strlen($senha) < 6) {
        flash('error', 'A senha deve ter pelo menos 6 caracteres.');
        redir('../setup.php?step=2');
    }

    try {
        include_once __DIR__ . '/connection.php';
        include_once __DIR__ . '/DAO/user_DAO.php';
        $cfg = dirname(__DIR__) . '/config.php';
        if (file_exists($cfg)) include_once $cfg;
        $c   = new connection();
        $pdo = $c->connect();
        $dao = new user_DAO();

        // Verifica se login já existe
        if ($dao->find_by_login($login, $pdo)) {
            flash('error', 'Este login já está em uso.');
            redir('../setup.php?step=2');
        }

        $dao->create($nome, $login, $senha, 'admin', $pdo);
        $_SESSION['wizard']['admin'] = true;
        redir('../setup.php?step=3');
    } catch (Exception $e) {
        flash('error', 'Erro ao criar Admin: ' . $e->getMessage());
        redir('../setup.php?step=2');
    }
}

/* ══════════════════════════════════════════════════════════
   STEP 3 — Cria conta Master
══════════════════════════════════════════════════════════ */
if ($action === 'save_master') {
    if (empty($_SESSION['wizard']['admin'])) redir('../setup.php?step=2');

    $nome    = trim($_POST['nome']    ?? '');
    $login   = trim($_POST['login']   ?? '');
    $senha   = trim($_POST['senha']   ?? '');
    $confirm = trim($_POST['confirmar'] ?? '');

    if (!$nome || !$login || !$senha) {
        flash('error', 'Preencha todos os campos da conta Master.');
        redir('../setup.php?step=3');
    }
    if ($senha !== $confirm) {
        flash('error', 'As senhas não coincidem.');
        redir('../setup.php?step=3');
    }
    if (strlen($senha) < 6) {
        flash('error', 'A senha deve ter pelo menos 6 caracteres.');
        redir('../setup.php?step=3');
    }

    try {
        include_once __DIR__ . '/connection.php';
        include_once __DIR__ . '/DAO/user_DAO.php';
        $cfg = dirname(__DIR__) . '/config.php';
        if (file_exists($cfg)) include_once $cfg;
        $c   = new connection();
        $pdo = $c->connect();
        $dao = new user_DAO();

        if ($dao->find_by_login($login, $pdo)) {
            flash('error', 'Este login já está em uso.');
            redir('../setup.php?step=3');
        }

        $dao->create($nome, $login, $senha, 'master', $pdo);
        $_SESSION['wizard']['master'] = true;
        redir('../setup.php?step=4');
    } catch (Exception $e) {
        flash('error', 'Erro ao criar Master: ' . $e->getMessage());
        redir('../setup.php?step=3');
    }
}

/* ══════════════════════════════════════════════════════════
   STEP 4 — Identidade do salão + SETUP_COMPLETE
══════════════════════════════════════════════════════════ */
if ($action === 'save_identity') {
    if (empty($_SESSION['wizard']['master'])) redir('../setup.php?step=3');

    $nome_salao   = trim($_POST['nome_salao']   ?? 'Espaço da Beleza');
    $local_salao  = trim($_POST['local_salao']  ?? '');
    $cor_primaria = trim($_POST['cor_primaria'] ?? '#7a3444');
    $cor_destaque = trim($_POST['cor_destaque'] ?? '#b06ab3');

    try {
        include_once __DIR__ . '/connection.php';
        include_once __DIR__ . '/DAO/settings_DAO.php';
        $cfg = dirname(__DIR__) . '/config.php';
        if (file_exists($cfg)) include_once $cfg;
        $c   = new connection();
        $pdo = $c->connect();
        $dao = new settings_DAO();

        $settings = [
            'nome_salao'   => $nome_salao,
            'local_salao'  => $local_salao,
            'cor_primaria' => $cor_primaria,
            'cor_destaque' => $cor_destaque,
            'logo_path'    => 'img/logo-full.png',
            'icon_path'    => 'img/logo.png',
        ];

        // Logo upload (opcional)
        if (!empty($_FILES['logo']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png','jpg','jpeg','gif','webp','svg'])) {
                $dest = dirname(__DIR__) . '/img/salon_logo.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    $settings['logo_path'] = 'img/salon_logo.' . $ext;
                    $settings['icon_path'] = 'img/salon_logo.' . $ext;
                }
            }
        }

        foreach ($settings as $k => $v) {
            $dao->set($k, $v, $pdo);
        }

        // Marca setup como completo em config.php
        $current_cfg = file_get_contents($cfg);
        if (strpos($current_cfg, 'SETUP_COMPLETE') === false) {
            file_put_contents($cfg, $current_cfg . "\ndefine('SETUP_COMPLETE', true);\n");
        }

        $_SESSION['wizard'] = [];
        flash('success', 'Setup concluído! Entre com sua conta Admin ou Master.');
        redir('../login.php');
    } catch (Exception $e) {
        flash('error', 'Erro ao salvar identidade: ' . $e->getMessage());
        redir('../setup.php?step=4');
    }
}

/* ══════════════════════════════════════════════════════════
   RUNTIME — Atualiza configurações (requer admin logado)
══════════════════════════════════════════════════════════ */
if ($action === 'update_identity') {
    if (!isset($_SESSION['user_id']) || $_SESSION['nivel'] !== 'admin') {
        redir('../login.php?erro=sem_permissao');
    }

    $nome_salao   = trim($_POST['nome_salao']   ?? '');
    $local_salao  = trim($_POST['local_salao']  ?? '');
    $cor_primaria = trim($_POST['cor_primaria'] ?? '#7a3444');
    $cor_destaque = trim($_POST['cor_destaque'] ?? '#b06ab3');

    try {
        include_once __DIR__ . '/connection.php';
        include_once __DIR__ . '/DAO/settings_DAO.php';
        $cfg = dirname(__DIR__) . '/config.php';
        if (file_exists($cfg)) include_once $cfg;
        $c   = new connection();
        $pdo = $c->connect();
        $dao = new settings_DAO();

        $dao->set('nome_salao',   $nome_salao,   $pdo);
        $dao->set('local_salao',  $local_salao,  $pdo);
        $dao->set('cor_primaria', $cor_primaria, $pdo);
        $dao->set('cor_destaque', $cor_destaque, $pdo);

        if (!empty($_FILES['logo']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png','jpg','jpeg','gif','webp','svg'])) {
                $dest = dirname(__DIR__) . '/img/salon_logo.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    $dao->set('logo_path', 'img/salon_logo.' . $ext, $pdo);
                    $dao->set('icon_path', 'img/salon_logo.' . $ext, $pdo);
                }
            }
        }

        flash('success', 'Configurações de identidade atualizadas!');
    } catch (Exception $e) {
        flash('error', 'Erro: ' . $e->getMessage());
    }
    redir('../setup.php');
}

/* ══════════════════════════════════════════════════════════
   RUNTIME — Adiciona usuário (master ou admin)
══════════════════════════════════════════════════════════ */
if ($action === 'add_user') {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['nivel'], ['master','admin'])) {
        redir('../login.php?erro=sem_permissao');
    }

    $nome   = trim($_POST['nome']   ?? '');
    $login  = trim($_POST['login']  ?? '');
    $senha  = trim($_POST['senha']  ?? '');
    $nivel  = trim($_POST['nivel']  ?? 'agente');

    // Agentes só podem criar agentes; masters criam agentes; admins criam qualquer nível
    $permitido = ['agente'];
    if ($_SESSION['nivel'] === 'admin') $permitido = ['agente','master','admin'];

    if (!in_array($nivel, $permitido)) $nivel = 'agente';
    if (!$nome || !$login || !$senha || strlen($senha) < 6) {
        flash('error', 'Preencha todos os campos. Senha mínima: 6 caracteres.');
        redir('../setup.php');
    }

    try {
        include_once __DIR__ . '/connection.php';
        include_once __DIR__ . '/DAO/user_DAO.php';
        $cfg = dirname(__DIR__) . '/config.php';
        if (file_exists($cfg)) include_once $cfg;
        $c   = new connection();
        $pdo = $c->connect();
        $dao = new user_DAO();

        if ($dao->find_by_login($login, $pdo)) {
            flash('error', 'Login já está em uso.');
            redir('../setup.php');
        }
        $dao->create($nome, $login, $senha, $nivel, $pdo);
        flash('success', "Usuário {$nome} criado com sucesso!");
    } catch (Exception $e) {
        flash('error', 'Erro: ' . $e->getMessage());
    }
    redir('../setup.php');
}

/* ══════════════════════════════════════════════════════════
   RUNTIME — Exclui usuário agente
══════════════════════════════════════════════════════════ */
if ($action === 'delete_user') {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['nivel'], ['master','admin'])) {
        redir('../login.php?erro=sem_permissao');
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        try {
            include_once __DIR__ . '/connection.php';
            include_once __DIR__ . '/DAO/user_DAO.php';
            $cfg = dirname(__DIR__) . '/config.php';
            if (file_exists($cfg)) include_once $cfg;
            $c   = new connection();
            $pdo = $c->connect();
            $dao = new user_DAO();
            $dao->delete($id, $pdo);
            flash('success', 'Usuário removido.');
        } catch (Exception $e) {
            flash('error', 'Erro: ' . $e->getMessage());
        }
    }
    redir('../setup.php');
}

/* ══════════════════════════════════════════════════════════
   RUNTIME — Altera senha do usuário logado
══════════════════════════════════════════════════════════ */
if ($action === 'change_password') {
    if (!isset($_SESSION['user_id'])) redir('../login.php');

    $atual   = trim($_POST['senha_atual']   ?? '');
    $nova    = trim($_POST['nova_senha']     ?? '');
    $confirm = trim($_POST['confirmar']      ?? '');

    if (!$atual || !$nova || strlen($nova) < 6) {
        flash('error', 'Preencha todos os campos. Nova senha mínima: 6 caracteres.');
        redir('../setup.php');
    }
    if ($nova !== $confirm) {
        flash('error', 'As novas senhas não coincidem.');
        redir('../setup.php');
    }

    try {
        include_once __DIR__ . '/connection.php';
        include_once __DIR__ . '/DAO/user_DAO.php';
        $cfg = dirname(__DIR__) . '/config.php';
        if (file_exists($cfg)) include_once $cfg;
        $c   = new connection();
        $pdo = $c->connect();
        $dao = new user_DAO();
        $user = $dao->find_by_id($_SESSION['user_id'], $pdo);

        if (!$user || !password_verify($atual, $user->senha_hash)) {
            flash('error', 'Senha atual incorreta.');
            redir('../setup.php');
        }
        $dao->change_password($_SESSION['user_id'], $nova, $pdo);
        flash('success', 'Senha alterada com sucesso!');
    } catch (Exception $e) {
        flash('error', 'Erro: ' . $e->getMessage());
    }
    redir('../setup.php');
}

redir('../setup.php');
