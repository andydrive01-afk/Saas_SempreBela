<?php
session_start();

/* ── Carrega config.php se existir ── */
$_cfg = __DIR__ . '/config.php';
if (file_exists($_cfg)) @include_once $_cfg;

$_setup_done = defined('SETUP_COMPLETE') && SETUP_COMPLETE;

/* ── Flash messages ── */
$_flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ── Modo: wizard ou runtime ── */
if (!$_setup_done) {
    /* ════════════════════════════════════════════════════════════
       MODO WIZARD — primeiro uso
    ════════════════════════════════════════════════════════════ */
    $step = max(1, (int)($_GET['step'] ?? 1));

    // Garante que steps anteriores estão completos antes de avançar
    if ($step >= 2 && empty($_SESSION['wizard']['db']))     { $step = 1; }
    if ($step >= 3 && empty($_SESSION['wizard']['admin']))  { $step = 2; }
    if ($step >= 4 && empty($_SESSION['wizard']['master'])) { $step = 3; }

    // Progresso visual
    $steps_labels = ['Banco de Dados','Conta Admin','Conta Master','Identidade'];
    $total_steps  = count($steps_labels);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/fonts.css" rel="stylesheet">
    <title>Configuração Inicial — Espaço da Beleza</title>
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { min-height:100vh; background:linear-gradient(135deg,#7a3444,#b06ab3);
           font-family:'Roboto',sans-serif; padding:30px 16px 60px; }

    .wiz-wrap { max-width:620px; margin:0 auto; }

    /* Header */
    .wiz-header { text-align:center; margin-bottom:28px; }
    .wiz-header h1 { color:#fff; font-size:24px; font-weight:700; margin-bottom:4px; }
    .wiz-header p  { color:rgba(255,255,255,.75); font-size:14px; }

    /* Progress indicator */
    .wiz-progress { display:flex; align-items:center; margin-bottom:32px; }
    .wiz-step { flex:1; text-align:center; position:relative; }
    .wiz-step:not(:last-child)::after {
        content:''; position:absolute; top:16px; left:60%; width:80%; height:2px;
        background:rgba(255,255,255,.3); z-index:0;
    }
    .wiz-step.done::after  { background:rgba(255,255,255,.8); }
    .wiz-dot {
        width:32px; height:32px; border-radius:50%;
        background:rgba(255,255,255,.2); border:2px solid rgba(255,255,255,.4);
        color:rgba(255,255,255,.6); font-weight:700; font-size:14px;
        display:flex; align-items:center; justify-content:center;
        margin:0 auto 6px; position:relative; z-index:1;
    }
    .wiz-step.done  .wiz-dot { background:#fff; border-color:#fff; color:#7a3444; }
    .wiz-step.active .wiz-dot { background:linear-gradient(135deg,#fff,#ffe); border-color:#fff; color:#7a3444; box-shadow:0 0 12px rgba(255,255,255,.5); }
    .wiz-label { font-size:11px; color:rgba(255,255,255,.6); font-weight:600; text-transform:uppercase; letter-spacing:.3px; }
    .wiz-step.done   .wiz-label,
    .wiz-step.active .wiz-label { color:#fff; }

    /* Card */
    .wiz-card { background:#fff; border-radius:18px; box-shadow:0 8px 40px rgba(0,0,0,.22);
                padding:36px 36px 30px; }
    .wiz-card-title { font-size:20px; font-weight:700; color:#333; margin-bottom:6px; display:flex; align-items:center; gap:10px; }
    .wiz-card-desc  { font-size:14px; color:#777; margin-bottom:28px; line-height:1.6; }

    /* Flash */
    .flash { padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; font-weight:600; }
    .flash.error   { background:#fdf0f0; color:#b91c1c; border:1.5px solid #f5b8b8; }
    .flash.success { background:#f0fdf4; color:#166534; border:1.5px solid #86efac; }

    /* Form */
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .form-group { margin-bottom:18px; }
    .form-group.full { grid-column:1/-1; }
    .form-group label { display:block; font-size:11px; font-weight:700; text-transform:uppercase;
                        letter-spacing:.4px; color:#666; margin-bottom:6px; }
    .form-group input, .form-group select {
        width:100%; padding:11px 14px; border:1.5px solid #ddd; border-radius:9px;
        font-size:15px; outline:none; transition:border-color .2s; background:#fafafa;
        font-family:inherit;
    }
    .form-group input:focus, .form-group select:focus { border-color:#7a3444; background:#fff; }
    .pass-wrap { position:relative; }
    .pass-wrap input { padding-right:42px; }
    .pass-eye { position:absolute; right:12px; top:50%; transform:translateY(-50%);
                cursor:pointer; font-size:17px; color:#bbb; }

    /* Driver selector */
    .driver-pills { display:flex; gap:10px; margin-bottom:20px; }
    .driver-pill  { flex:1; text-align:center; padding:12px 8px; border:2px solid #e0e0e0;
                    border-radius:12px; cursor:pointer; font-size:13px; font-weight:700;
                    color:#666; transition:all .2s; user-select:none; }
    .driver-pill:hover  { border-color:#b06ab3; color:#7a3444; }
    .driver-pill.active { border-color:#7a3444; background:#7a3444; color:#fff; }
    .driver-pill input  { display:none; }

    /* AJAX buttons */
    .ajax-btn { padding:9px 18px; border:1.5px solid #7a3444; border-radius:8px;
                background:#fff; color:#7a3444; font-size:13px; font-weight:700;
                cursor:pointer; transition:all .2s; }
    .ajax-btn:hover { background:#7a3444; color:#fff; }
    .ajax-btn:disabled { opacity:.5; cursor:not-allowed; }
    .ajax-row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:18px; }
    .ajax-msg { font-size:13px; font-weight:600; }
    .ajax-msg.ok  { color:#166534; }
    .ajax-msg.err { color:#b91c1c; }

    /* Submit */
    .wiz-footer { display:flex; align-items:center; justify-content:space-between;
                  margin-top:28px; padding-top:20px; border-top:1px solid #f0f0f0; }
    .wiz-btn { padding:13px 32px; border:none; border-radius:10px;
               background:linear-gradient(135deg,#7a3444,#b06ab3); color:#fff;
               font-size:16px; font-weight:700; cursor:pointer; transition:opacity .2s; }
    .wiz-btn:hover { opacity:.9; }
    .wiz-hint { font-size:12px; color:#bbb; }

    /* Color swatch */
    .color-wrap { display:flex; align-items:center; gap:10px; }
    .color-wrap input[type=color] { width:44px; height:44px; border:none; border-radius:8px;
                                     cursor:pointer; padding:2px; }
    .color-wrap input[type=text]  { flex:1; }
    </style>
</head>
<body>
<div class="wiz-wrap">

    <!-- Header -->
    <div class="wiz-header">
        <h1>✨ Bem-vindo ao Espaço da Beleza</h1>
        <p>Configure o sistema em 4 passos rápidos para começar a usar</p>
    </div>

    <!-- Progress -->
    <div class="wiz-progress">
    <?php for ($i=1; $i<=$total_steps; $i++):
        $cls = ($i < $step) ? 'done' : (($i == $step) ? 'active' : '');
    ?>
        <div class="wiz-step <?=$cls?>">
            <div class="wiz-dot"><?=$i < $step ? '✓' : $i?></div>
            <div class="wiz-label"><?=$steps_labels[$i-1]?></div>
        </div>
    <?php endfor; ?>
    </div>

    <!-- Card -->
    <div class="wiz-card">

    <?php if ($_flash): ?>
        <div class="flash <?=$_flash['type']?>"><?=htmlspecialchars($_flash['msg'])?></div>
    <?php endif; ?>

    <!-- ═══════════ STEP 1: BANCO DE DADOS ═══════════ -->
    <?php if ($step === 1): ?>
    <div class="wiz-card-title">🗄️ Banco de Dados</div>
    <div class="wiz-card-desc">Escolha o tipo de banco de dados e informe as credenciais.
    Use <strong>Testar Conexão</strong> para validar e depois <strong>Instalar Banco</strong> para criar as tabelas.</div>

    <!-- Driver pills -->
    <div class="driver-pills">
        <label class="driver-pill active" id="pill-mysql">
            <input type="radio" name="db_driver" value="mysql" checked> 🐬 MySQL / MariaDB
        </label>
        <label class="driver-pill" id="pill-sqlite">
            <input type="radio" name="db_driver" value="sqlite"> 📄 SQLite
        </label>
        <label class="driver-pill" id="pill-pgsql">
            <input type="radio" name="db_driver" value="pgsql"> 🐘 PostgreSQL
        </label>
    </div>

    <form method="POST" action="pdo/wizard_action.php" id="form-db">
        <input type="hidden" name="_action" value="save_db">
        <input type="hidden" name="db_driver" id="hid-driver" value="mysql">

        <!-- MySQL / PostgreSQL fields -->
        <div id="fields-server">
            <div class="form-row">
                <div class="form-group">
                    <label>Servidor (Host)</label>
                    <input type="text" name="db_host" id="db_host" value="localhost">
                </div>
                <div class="form-group">
                    <label>Porta</label>
                    <input type="number" name="db_port" id="db_port" value="3306">
                </div>
            </div>
            <div class="form-group">
                <label>Nome do Banco de Dados</label>
                <input type="text" name="db_name" id="db_name" placeholder="nome_do_banco">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Usuário</label>
                    <input type="text" name="db_user" id="db_user" placeholder="usuario_db">
                </div>
                <div class="form-group">
                    <label>Senha</label>
                    <div class="pass-wrap">
                        <input type="password" name="db_pass" id="db_pass" placeholder="Senha do banco">
                        <span class="pass-eye" onclick="toggle('db_pass',this)">👁</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SQLite field -->
        <div id="fields-sqlite" style="display:none">
            <div class="form-group">
                <label>Caminho do arquivo SQLite (deixe em branco para padrão)</label>
                <input type="text" name="db_file" id="db_file" placeholder="Ex: /home/usuario/data/salao.sqlite">
            </div>
            <p style="font-size:13px;color:#888;margin-top:-12px;margin-bottom:16px;">
                💡 Se em branco, será criado em <code>data/salao.sqlite</code> dentro do sistema.
            </p>
        </div>

        <!-- Test / Install buttons -->
        <div class="ajax-row">
            <button type="button" class="ajax-btn" id="btn-test">🔌 Testar Conexão</button>
            <button type="button" class="ajax-btn" id="btn-install" disabled>⚙️ Instalar Banco</button>
            <span class="ajax-msg" id="ajax-result"></span>
        </div>

        <div class="wiz-footer">
            <span class="wiz-hint">Teste e instale o banco antes de continuar</span>
            <button type="submit" class="wiz-btn" id="btn-next" disabled>Próximo →</button>
        </div>
    </form>

    <!-- ═══════════ STEP 2: ADMIN ═══════════ -->
    <?php elseif ($step === 2): ?>
    <div class="wiz-card-title">🔐 Conta Administrador</div>
    <div class="wiz-card-desc">
        O <strong>Admin</strong> tem acesso total ao sistema incluindo esta página de Configurações,
        backup e gerenciamento de banco de dados. Guarde bem estas credenciais.
    </div>
    <form method="POST" action="pdo/wizard_action.php">
        <input type="hidden" name="_action" value="save_admin">
        <div class="form-group">
            <label>Nome Completo</label>
            <input type="text" name="nome" required placeholder="Seu nome completo" autofocus>
        </div>
        <div class="form-group">
            <label>Login (usuário)</label>
            <input type="text" name="login" required placeholder="Ex: admin" autocomplete="off">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Senha (mín. 6 caracteres)</label>
                <div class="pass-wrap">
                    <input type="password" name="senha" id="adm-pass" required minlength="6">
                    <span class="pass-eye" onclick="toggle('adm-pass',this)">👁</span>
                </div>
            </div>
            <div class="form-group">
                <label>Confirmar Senha</label>
                <div class="pass-wrap">
                    <input type="password" name="confirmar" id="adm-confirm" required minlength="6">
                    <span class="pass-eye" onclick="toggle('adm-confirm',this)">👁</span>
                </div>
            </div>
        </div>
        <div class="wiz-footer">
            <span class="wiz-hint">Nível: Admin (acesso total)</span>
            <button type="submit" class="wiz-btn">Próximo →</button>
        </div>
    </form>

    <!-- ═══════════ STEP 3: MASTER ═══════════ -->
    <?php elseif ($step === 3): ?>
    <div class="wiz-card-title">👑 Conta Master</div>
    <div class="wiz-card-desc">
        O <strong>Master</strong> pode gerenciar serviços, produtos, atendentes, clientes e
        adicionar usuários do tipo Agente. Ideal para a gerente do salão.
    </div>
    <form method="POST" action="pdo/wizard_action.php">
        <input type="hidden" name="_action" value="save_master">
        <div class="form-group">
            <label>Nome Completo</label>
            <input type="text" name="nome" required placeholder="Nome da gerente" autofocus>
        </div>
        <div class="form-group">
            <label>Login (usuário)</label>
            <input type="text" name="login" required placeholder="Ex: gerente" autocomplete="off">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Senha (mín. 6 caracteres)</label>
                <div class="pass-wrap">
                    <input type="password" name="senha" id="mst-pass" required minlength="6">
                    <span class="pass-eye" onclick="toggle('mst-pass',this)">👁</span>
                </div>
            </div>
            <div class="form-group">
                <label>Confirmar Senha</label>
                <div class="pass-wrap">
                    <input type="password" name="confirmar" id="mst-confirm" required minlength="6">
                    <span class="pass-eye" onclick="toggle('mst-confirm',this)">👁</span>
                </div>
            </div>
        </div>
        <div class="wiz-footer">
            <span class="wiz-hint">Nível: Master (gerência do salão)</span>
            <button type="submit" class="wiz-btn">Próximo →</button>
        </div>
    </form>

    <!-- ═══════════ STEP 4: IDENTIDADE ═══════════ -->
    <?php elseif ($step === 4): ?>
    <div class="wiz-card-title">💅 Identidade do Salão</div>
    <div class="wiz-card-desc">
        Personalize o nome, endereço, logo e as cores do sistema. Você pode alterar isso depois nas Configurações.
    </div>
    <form method="POST" action="pdo/wizard_action.php" enctype="multipart/form-data">
        <input type="hidden" name="_action" value="save_identity">
        <div class="form-group">
            <label>Nome do Salão</label>
            <input type="text" name="nome_salao" required value="Espaço da Beleza" placeholder="Nome do seu salão" autofocus>
        </div>
        <div class="form-group">
            <label>Endereço / Localização</label>
            <input type="text" name="local_salao" placeholder="Rua, número, cidade">
        </div>
        <div class="form-group">
            <label>Logo (PNG, JPG, SVG — opcional)</label>
            <input type="file" name="logo" accept="image/*" style="background:#fff;padding:8px;">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Cor Primária (fundo/menu)</label>
                <div class="color-wrap">
                    <input type="color" id="cp-pick" value="#7a3444" oninput="document.getElementById('cp-txt').value=this.value">
                    <input type="text"  id="cp-txt"  name="cor_primaria" value="#7a3444" oninput="document.getElementById('cp-pick').value=this.value">
                </div>
            </div>
            <div class="form-group">
                <label>Cor Destaque (botões/acentos)</label>
                <div class="color-wrap">
                    <input type="color" id="cd-pick" value="#b06ab3" oninput="document.getElementById('cd-txt').value=this.value">
                    <input type="text"  id="cd-txt"  name="cor_destaque" value="#b06ab3" oninput="document.getElementById('cd-pick').value=this.value">
                </div>
            </div>
        </div>
        <div class="wiz-footer">
            <span class="wiz-hint">Quase lá! 🎉</span>
            <button type="submit" class="wiz-btn">✅ Concluir Setup</button>
        </div>
    </form>
    <?php endif; ?>

    </div><!-- /.wiz-card -->
</div><!-- /.wiz-wrap -->

<script>
/* ── Password toggle ── */
function toggle(id, el) {
    var i = document.getElementById(id);
    i.type = i.type === 'password' ? 'text' : 'password';
    el.textContent = i.type === 'password' ? '👁' : '🙈';
}

/* ── Driver pills ── */
document.querySelectorAll('.driver-pill').forEach(function(pill) {
    pill.addEventListener('click', function() {
        document.querySelectorAll('.driver-pill').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        var v = this.querySelector('input').value;
        document.getElementById('hid-driver').value = v;
        document.getElementById('fields-server').style.display  = v === 'sqlite' ? 'none' : 'block';
        document.getElementById('fields-sqlite').style.display  = v === 'sqlite' ? 'block' : 'none';
        if (v === 'pgsql') document.getElementById('db_port').value = 5432;
        if (v === 'mysql') document.getElementById('db_port').value = 3306;
        // Reset test state
        document.getElementById('btn-install').disabled = true;
        document.getElementById('btn-next').disabled = true;
        document.getElementById('ajax-result').textContent = '';
        document.getElementById('ajax-result').className = 'ajax-msg';
    });
});

/* ── AJAX Test ── */
function getDbParams() {
    var d   = document.getElementById('hid-driver').value;
    var p   = new URLSearchParams();
    p.set('db_driver', d);
    if (d === 'sqlite') {
        p.set('db_file', document.getElementById('db_file').value);
    } else {
        p.set('db_host', document.getElementById('db_host').value);
        p.set('db_port', document.getElementById('db_port').value);
        p.set('db_name', document.getElementById('db_name').value);
        p.set('db_user', document.getElementById('db_user').value);
        p.set('db_pass', document.getElementById('db_pass').value);
    }
    return p;
}

document.getElementById('btn-test').addEventListener('click', function() {
    var btn = this;
    var msg = document.getElementById('ajax-result');
    btn.disabled = true;
    btn.textContent = '⏳ Testando…';
    msg.textContent = ''; msg.className = 'ajax-msg';

    fetch('pdo/test_db.php', {method:'POST', body: getDbParams()})
    .then(r => r.json()).then(function(d) {
        msg.textContent = d.msg;
        msg.className   = 'ajax-msg ' + (d.ok ? 'ok' : 'err');
        document.getElementById('btn-install').disabled = !d.ok;
        if (!d.ok) document.getElementById('btn-next').disabled = true;
    }).catch(function() {
        msg.textContent = '❌ Erro de comunicação.';
        msg.className   = 'ajax-msg err';
    }).finally(function() {
        btn.disabled = false;
        btn.textContent = '🔌 Testar Conexão';
    });
});

document.getElementById('btn-install').addEventListener('click', function() {
    var btn = this;
    var msg = document.getElementById('ajax-result');
    btn.disabled = true;
    btn.textContent = '⏳ Instalando…';

    fetch('pdo/install_db.php', {method:'POST', body: getDbParams()})
    .then(r => r.json()).then(function(d) {
        msg.textContent = d.msg;
        msg.className   = 'ajax-msg ' + (d.ok ? 'ok' : 'err');
        document.getElementById('btn-next').disabled = !d.ok;
    }).catch(function() {
        msg.textContent = '❌ Erro de comunicação.';
        msg.className   = 'ajax-msg err';
    }).finally(function() {
        btn.disabled = false;
        btn.textContent = '⚙️ Instalar Banco';
    });
});
</script>
</body>
</html>
<?php

} else {
    /* ════════════════════════════════════════════════════════════
       MODO RUNTIME — configurações (requer login Admin)
    ════════════════════════════════════════════════════════════ */
    if (!isset($_SESSION['user_id']) || $_SESSION['nivel'] !== 'admin') {
        header('Location: login.php?erro=sem_permissao&redirect=setup.php');
        exit;
    }

    include_once 'inc/settings.php';

    // Carrega usuários
    include_once 'pdo/DAO/user_DAO.php';
    $udao    = new user_DAO();
    $usuarios = [];
    try { $usuarios = $udao->list_all($conn); } catch (Exception $e) {}

    $driver_atual = defined('DB_DRIVER') ? DB_DRIVER : 'mysql';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?=$salon_icon?>">
    <link href="css/fonts.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/theme.php" rel="stylesheet">
    <title>Configurações — <?=htmlspecialchars($salon_name)?></title>
    <style>
    .setup-wrap { max-width:860px; margin:0 auto; padding:30px 16px 60px; display:flex; flex-direction:column; gap:24px; }
    .setup-card { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.08); overflow:hidden; }
    .setup-card-header { background:linear-gradient(135deg,var(--cor-primaria,#7a3444),var(--cor-destaque,#b06ab3)); color:#fff; padding:16px 24px; font-size:16px; font-weight:700; display:flex; align-items:center; gap:10px; }
    .setup-card-body { padding:24px; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .form-group { margin-bottom:16px; }
    .form-group.full { grid-column:1/-1; }
    .form-group label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#666; margin-bottom:6px; }
    .form-group input, .form-group select { width:100%; padding:10px 13px; border:1.5px solid #ddd; border-radius:8px; font-size:14px; outline:none; transition:border-color .2s; }
    .form-group input:focus, .form-group select:focus { border-color:var(--cor-primaria,#7a3444); }
    .setup-btn { padding:10px 24px; background:linear-gradient(135deg,var(--cor-primaria,#7a3444),var(--cor-destaque,#b06ab3)); color:#fff; border:none; border-radius:9px; font-size:14px; font-weight:700; cursor:pointer; display:inline-block; }
    .setup-btn:hover { opacity:.9; }
    .btn-sm { padding:6px 14px; font-size:13px; }
    .btn-danger { background:linear-gradient(135deg,#b91c1c,#dc2626); }

    /* Flash */
    .flash { padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; font-weight:600; }
    .flash.error   { background:#fdf0f0; color:#b91c1c; border:1.5px solid #f5b8b8; }
    .flash.success { background:#f0fdf4; color:#166534; border:1.5px solid #86efac; }

    /* Users table */
    .users-table { width:100%; border-collapse:collapse; font-size:14px; }
    .users-table th { background:#f8f8f8; color:#555; text-transform:uppercase; font-size:11px; letter-spacing:.3px; padding:10px 12px; text-align:left; }
    .users-table td { padding:10px 12px; border-top:1px solid #f0f0f0; }
    .nivel-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; }
    .nivel-admin  { background:#7a3444; color:#fff; }
    .nivel-master { background:#b06ab3; color:#fff; }
    .nivel-agente { background:#e5e7eb; color:#555; }

    /* Color wrap */
    .color-wrap { display:flex; align-items:center; gap:10px; }
    .color-wrap input[type=color] { width:44px; height:44px; border:none; border-radius:8px; cursor:pointer; padding:2px; }
    .color-wrap input[type=text]  { flex:1; }

    /* pass toggle */
    .pass-wrap { position:relative; }
    .pass-wrap input { padding-right:42px; }
    .pass-eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:17px; color:#bbb; }

    /* Topbar */
    .setup-topbar { display:flex; align-items:center; justify-content:space-between; max-width:860px; margin:0 auto; padding:20px 16px 0; }
    .setup-topbar a { color:#fff; text-decoration:none; font-size:14px; font-weight:600; opacity:.85; }
    .setup-topbar a:hover { opacity:1; }
    body { background:linear-gradient(135deg,#7a3444,#b06ab3) fixed; min-height:100vh; font-family:'Roboto',sans-serif; }
    </style>
</head>
<body>
<div class="setup-topbar">
    <a href="index.php">← Voltar ao início</a>
    <span style="color:#fff;font-size:13px;opacity:.8">Logado como <strong><?=htmlspecialchars($_SESSION['nome'])?></strong> (<?=$_SESSION['nivel']?>) · <a href="pdo/do_logout.php">Sair</a></span>
</div>

<div class="setup-wrap">

    <?php if ($_flash): ?>
    <div class="flash <?=$_flash['type']?>"><?=htmlspecialchars($_flash['msg'])?></div>
    <?php endif; ?>

    <!-- ══ 1. BANCO DE DADOS (informativo) ══ -->
    <div class="setup-card">
        <div class="setup-card-header">🗄️ Banco de Dados</div>
        <div class="setup-card-body">
            <p style="font-size:14px;color:#555;line-height:1.7;margin-bottom:16px;">
                Driver atual: <strong><?=htmlspecialchars(strtoupper($driver_atual))?></strong>
                <?php if (defined('DB_HOST')): ?>· Servidor: <strong><?=htmlspecialchars(DB_HOST)?></strong>
                · Banco: <strong><?=htmlspecialchars(defined('DB_NAME') ? DB_NAME : '')?></strong><?php endif; ?>
                <?php if ($driver_atual === 'sqlite' && defined('DB_FILE')): ?>· Arquivo: <code><?=htmlspecialchars(DB_FILE)?></code><?php endif; ?>
            </p>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button class="setup-btn btn-sm" onclick="document.getElementById('db-test-result').textContent='Testando…';
                    fetch('pdo/test_db.php',{method:'POST'}).then(r=>r.json()).then(d=>{var m=document.getElementById('db-test-result');m.textContent=d.msg;m.style.color=d.ok?'green':'red';});">
                    🔌 Testar Conexão
                </button>
                <button class="setup-btn btn-sm" onclick="if(confirm('Instalar/atualizar banco de dados?'))
                    fetch('pdo/install_db.php',{method:'POST'}).then(r=>r.json()).then(d=>{var m=document.getElementById('db-test-result');m.textContent=d.msg;m.style.color=d.ok?'green':'red';});">
                    ⚙️ Instalar / Atualizar
                </button>
                <span id="db-test-result" style="font-size:13px;font-weight:600;align-self:center;"></span>
            </div>
        </div>
    </div>

    <!-- ══ 2. IDENTIDADE ══ -->
    <div class="setup-card">
        <div class="setup-card-header">💅 Identidade do Salão</div>
        <div class="setup-card-body">
        <form method="POST" action="pdo/wizard_action.php" enctype="multipart/form-data">
            <input type="hidden" name="_action" value="update_identity">
            <div class="form-row">
                <div class="form-group">
                    <label>Nome do Salão</label>
                    <input type="text" name="nome_salao" value="<?=htmlspecialchars($salon_name)?>">
                </div>
                <div class="form-group">
                    <label>Endereço / Localização</label>
                    <input type="text" name="local_salao" value="<?=htmlspecialchars($salon_local)?>">
                </div>
            </div>
            <div class="form-group">
                <label>Logo (PNG, JPG, SVG)</label>
                <input type="file" name="logo" accept="image/*" style="padding:8px;">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Cor Primária</label>
                    <div class="color-wrap">
                        <input type="color" id="cp2-pick" value="<?=$cor_primaria?>" oninput="document.getElementById('cp2-txt').value=this.value">
                        <input type="text"  id="cp2-txt"  name="cor_primaria" value="<?=$cor_primaria?>" oninput="document.getElementById('cp2-pick').value=this.value">
                    </div>
                </div>
                <div class="form-group">
                    <label>Cor Destaque</label>
                    <div class="color-wrap">
                        <input type="color" id="cd2-pick" value="<?=$cor_destaque?>" oninput="document.getElementById('cd2-txt').value=this.value">
                        <input type="text"  id="cd2-txt"  name="cor_destaque" value="<?=$cor_destaque?>" oninput="document.getElementById('cd2-pick').value=this.value">
                    </div>
                </div>
            </div>
            <div style="text-align:right">
                <button type="submit" class="setup-btn">💾 Salvar Identidade</button>
            </div>
        </form>
        </div>
    </div>

    <!-- ══ 3. USUÁRIOS ══ -->
    <div class="setup-card">
        <div class="setup-card-header">👥 Usuários do Sistema</div>
        <div class="setup-card-body">
            <p style="font-size:13px;color:#777;margin-bottom:18px;line-height:1.6;">
                <strong>Admin</strong> — acesso total incluindo configurações.<br>
                <strong>Master</strong> — gerencia serviços, produtos, atendentes e agentes.<br>
                <strong>Agente</strong> — apenas registra atendimentos e consulta informações.
            </p>

            <!-- Tabela de usuários -->
            <?php if ($usuarios): ?>
            <table class="users-table" style="margin-bottom:24px;">
                <thead>
                    <tr><th>Nome</th><th>Login</th><th>Nível</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?=htmlspecialchars($u->nome)?></td>
                    <td><?=htmlspecialchars($u->login)?></td>
                    <td><span class="nivel-badge nivel-<?=$u->nivel?>"><?=ucfirst($u->nivel)?></span></td>
                    <td><?=$u->ativo ? '<span style="color:#166534;font-weight:600;">Ativo</span>' : '<span style="color:#b91c1c;">Inativo</span>'?></td>
                    <td>
                    <?php if ($u->nivel === 'agente' && $u->id != $_SESSION['user_id']): ?>
                        <form method="POST" action="pdo/wizard_action.php" style="display:inline" onsubmit="return confirm('Excluir agente <?=htmlspecialchars($u->nome)?>?')">
                            <input type="hidden" name="_action" value="delete_user">
                            <input type="hidden" name="id" value="<?=$u->id?>">
                            <button type="submit" class="setup-btn btn-sm btn-danger">✕</button>
                        </form>
                    <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Adicionar usuário -->
            <details style="margin-top:8px;">
                <summary style="cursor:pointer;font-weight:700;font-size:14px;color:#7a3444;user-select:none;">➕ Adicionar Usuário</summary>
                <form method="POST" action="pdo/wizard_action.php" style="margin-top:16px;">
                    <input type="hidden" name="_action" value="add_user">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nome Completo</label>
                            <input type="text" name="nome" required>
                        </div>
                        <div class="form-group">
                            <label>Login</label>
                            <input type="text" name="login" required autocomplete="off">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Senha (mín. 6 caracteres)</label>
                            <input type="password" name="senha" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Nível</label>
                            <select name="nivel">
                                <option value="agente">Agente</option>
                                <?php if ($_SESSION['nivel'] === 'admin'): ?>
                                <option value="master">Master</option>
                                <option value="admin">Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div style="text-align:right">
                        <button type="submit" class="setup-btn">Criar Usuário</button>
                    </div>
                </form>
            </details>
        </div>
    </div>

    <!-- ══ 4. MINHA SENHA ══ -->
    <div class="setup-card">
        <div class="setup-card-header">🔑 Alterar Minha Senha</div>
        <div class="setup-card-body">
        <form method="POST" action="pdo/wizard_action.php">
            <input type="hidden" name="_action" value="change_password">
            <div class="form-row">
                <div class="form-group">
                    <label>Senha Atual</label>
                    <div class="pass-wrap">
                        <input type="password" name="senha_atual" id="p-atual" required>
                        <span class="pass-eye" onclick="toggle('p-atual',this)">👁</span>
                    </div>
                </div>
                <div class="form-group" style="grid-column:1/-1"></div>
                <div class="form-group">
                    <label>Nova Senha</label>
                    <div class="pass-wrap">
                        <input type="password" name="nova_senha" id="p-nova" required minlength="6">
                        <span class="pass-eye" onclick="toggle('p-nova',this)">👁</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirmar Nova Senha</label>
                    <div class="pass-wrap">
                        <input type="password" name="confirmar" id="p-confirm" required minlength="6">
                        <span class="pass-eye" onclick="toggle('p-confirm',this)">👁</span>
                    </div>
                </div>
            </div>
            <div style="text-align:right">
                <button type="submit" class="setup-btn">🔒 Alterar Senha</button>
            </div>
        </form>
        </div>
    </div>

    <!-- ══ 5. BACKUP ══ -->
    <div class="setup-card">
        <div class="setup-card-header">💾 Backup do Banco de Dados</div>
        <div class="setup-card-body">
            <p style="margin:0 0 16px;color:#555;font-size:14px;line-height:1.6;">
                Gera um arquivo <strong>.sql</strong> completo com todas as tabelas e dados do sistema.
                Salve periodicamente em local seguro.
            </p>
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                <a href="pdo/backup_db.php" class="setup-btn" style="text-decoration:none;padding:10px 24px;">
                    ⬇️ Baixar Backup SQL
                </a>
                <span style="font-size:12px;color:#aaa;">
                    backup_<?=defined('DB_NAME')?DB_NAME:'db'?>_<?=date('Y-m-d')?>.sql
                </span>
            </div>
        </div>
    </div>

</div><!-- /.setup-wrap -->

<script>
function toggle(id, el) {
    var i = document.getElementById(id);
    i.type = i.type === 'password' ? 'text' : 'password';
    el.textContent = i.type === 'password' ? '👁' : '🙈';
}
</script>
</body>
</html>
<?php } ?>
