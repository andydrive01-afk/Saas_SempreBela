<?php
/* ─── Carrega config e settings sem redirecionar ─── */
$_cfg_path   = __DIR__ . '/config.php';
$_cfg_exists = file_exists($_cfg_path);
$_db_ok      = false;
$conn        = null;

if ($_cfg_exists) {
    include_once 'pdo/connection.php';
    try {
        $_sc    = new connection();
        $conn   = $_sc->connect();
        $_db_ok = true;
    } catch (Exception $_e) {
        $_db_ok = false;
    }
}

/* Lê credenciais salvas para pré-preencher o form */
$_saved_cfg = [];
if ($_cfg_exists) {
    include_once $_cfg_path;
    $_saved_cfg = [
        'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
        'name' => defined('DB_NAME') ? DB_NAME : '',
        'user' => defined('DB_USER') ? DB_USER : '',
        'port' => defined('DB_PORT') ? DB_PORT : 3306,
    ];
}

/* Lê configurações visuais do banco (só se conectado) */
$salon_name   = 'Espaço da Beleza Lucia Reis';
$salon_local  = 'Santana do Livramento, RS';
$cor_primaria = '#7a3444';
$cor_destaque = '#b06ab3';
$logo_path    = 'img/logo-full.png';

if ($_db_ok && $conn) {
    include_once 'pdo/DAO/settings_DAO.php';
    $_dao = new settings_DAO();
    $_s   = $_dao->get_all($conn);
    $salon_name   = $_s['nome_salao']   ?? $salon_name;
    $salon_local  = $_s['local_salao']  ?? $salon_local;
    $cor_primaria = $_s['cor_primaria'] ?? $cor_primaria;
    $cor_destaque = $_s['cor_destaque'] ?? $cor_destaque;
    $logo_path    = $_s['logo_path']    ?? $logo_path;
}

$is_first_run = isset($_GET['first_run']) || !$_cfg_exists;
$saved        = isset($_GET['saved']) && $_GET['saved'] == '1';
$db_saved     = isset($_GET['db']) && $_GET['db'] == '1';
$error        = $_GET['error'] ?? '';
$db_error_msg = (!$_cfg_exists ? '' : (!$_db_ok ? 'Não foi possível conectar ao banco com as credenciais salvas.' : ''));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/fonts.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <?php if ($_db_ok): ?><link href="css/theme.php" rel="stylesheet"><?php endif; ?>
    <script src="js/jquery-3.5.1.min.js"></script>
    <link rel="icon" href="<?=$logo_path?>">
    <title>Configurações — <?=$salon_name?></title>
    <style>
        body { background: #f4f0f4; font-family: 'Roboto', sans-serif; margin: 0; }

        .setup-page-header {
            background: linear-gradient(135deg, <?=$cor_primaria?>, <?=$cor_destaque?>);
            color: #fff; padding: 0 32px;
            display: flex; align-items: center; justify-content: space-between;
            height: 70px; box-shadow: 0 2px 8px rgba(0,0,0,.18);
        }
        .setup-page-header h1 { margin:0; font-size:22px; font-weight:700; }
        .setup-back { color:#fff; text-decoration:none; font-size:14px; }
        .setup-back:hover { opacity:.8; }

        .setup-wrap { max-width:780px; margin:36px auto 60px; display:flex; flex-direction:column; gap:28px; padding:0 16px; }

        .setup-card { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.08); overflow:hidden; }
        .setup-card-header {
            background: linear-gradient(135deg, <?=$cor_primaria?>, <?=$cor_destaque?>);
            color:#fff; padding:14px 24px; font-size:15px; font-weight:700;
            display:flex; align-items:center; gap:10px;
        }
        .setup-card-body { padding:24px; }

        .setup-row { display:flex; gap:16px; flex-wrap:wrap; }
        .setup-row .setup-field { flex:1; min-width:160px; }

        .setup-field { margin-bottom:20px; }
        .setup-field:last-child { margin-bottom:0; }
        .setup-field label { display:block; font-size:12px; font-weight:700; color:#666; margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px; }

        .setup-field input[type="text"],
        .setup-field input[type="password"],
        .setup-field input[type="number"] {
            width:100%; box-sizing:border-box; padding:10px 14px;
            border:1.5px solid #ddd; border-radius:8px; font-size:15px;
            transition:border-color .2s; outline:none; background:#fafafa;
        }
        .setup-field input:focus { border-color:<?=$cor_primaria?>; background:#fff; }

        .db-actions { display:flex; gap:10px; margin-top:20px; flex-wrap:wrap; }
        .btn-outline {
            padding:10px 22px; border-radius:8px; font-size:14px; font-weight:700;
            cursor:pointer; border:2px solid <?=$cor_primaria?>; color:<?=$cor_primaria?>;
            background:#fff; transition:all .2s;
        }
        .btn-outline:hover { background:<?=$cor_primaria?>; color:#fff; }
        .btn-outline:disabled { opacity:.5; cursor:default; }

        .db-status { margin-top:12px; padding:10px 14px; border-radius:8px; font-size:13px; font-weight:600; display:none; }
        .db-status.ok    { background:#eafaf1; color:#1a7a45; border:1.5px solid #a8e6c0; }
        .db-status.error { background:#fdf0f0; color:#b91c1c; border:1.5px solid #f5b8b8; }

        /* Color pickers */
        .color-row { display:flex; gap:24px; flex-wrap:wrap; }
        .color-pick { flex:1; min-width:180px; }
        .color-pick label { display:block; font-size:12px; font-weight:700; color:#666; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
        .color-pick-inner { display:flex; align-items:center; gap:12px; }
        .color-pick input[type="color"] { width:52px; height:52px; border:none; border-radius:10px; cursor:pointer; padding:2px; background:#f0f0f0; }
        .color-hex { flex:1; padding:9px 12px; border:1.5px solid #ddd; border-radius:8px; font-size:14px; font-family:monospace; outline:none; }
        .color-preview-bar {
            margin-top:16px; height:44px; border-radius:10px;
            background: linear-gradient(135deg, <?=$cor_primaria?>, <?=$cor_destaque?>);
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:13px; font-weight:600; transition:background .3s;
        }

        /* Logo upload */
        .logo-upload-area {
            border:2px dashed #ccc; border-radius:12px; padding:28px; text-align:center;
            cursor:pointer; transition:border-color .2s,background .2s; position:relative;
        }
        .logo-upload-area:hover { border-color:<?=$cor_primaria?>; background:#faf5fa; }
        .logo-upload-area input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
        .logo-upload-area img#logo-preview { max-height:100px; max-width:100%; border-radius:8px; margin:0 auto 10px; display:block; }
        .logo-upload-area p { margin:0; color:#888; font-size:14px; }
        .logo-upload-area .upload-hint { margin-top:6px; font-size:12px; color:#bbb; }

        /* Save row */
        .setup-save-row { display:flex; justify-content:flex-end; gap:12px; margin-top:4px; }
        .setup-btn {
            padding:13px 36px; border:none; border-radius:10px; font-size:16px; font-weight:700;
            color:#fff; cursor:pointer; background:linear-gradient(135deg, <?=$cor_primaria?>, <?=$cor_destaque?>);
            box-shadow:0 2px 8px rgba(0,0,0,.12); transition:opacity .2s,transform .1s;
        }
        .setup-btn:hover { opacity:.88; transform:translateY(-1px); }

        /* Alerts */
        .alert { padding:14px 20px; border-radius:10px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#eafaf1; color:#1a7a45; border:1.5px solid #a8e6c0; }
        .alert-error   { background:#fdf0f0; color:#b91c1c; border:1.5px solid #f5b8b8; }
        .alert-info    { background:#eff6ff; color:#1d4ed8; border:1.5px solid #bfdbfe; }

        /* Disabled overlay for sections that need DB first */
        .needs-db { position:relative; }
        .needs-db.locked::after {
            content:'🔒 Configure o banco de dados primeiro';
            position:absolute; inset:0; background:rgba(255,255,255,.82);
            display:flex; align-items:center; justify-content:center;
            font-size:15px; font-weight:700; color:#888; border-radius:14px;
            backdrop-filter:blur(2px);
        }

        /* password toggle */
        .pass-wrap { position:relative; }
        .pass-wrap input { padding-right:42px; }
        .pass-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:17px; user-select:none; color:#999; }
    </style>
</head>
<body>
    <div class="setup-page-header" id="setup-header">
        <h1>⚙️ Configurações do Salão</h1>
        <?php if ($_db_ok): ?>
        <a href="index.php" class="setup-back">← Voltar ao início</a>
        <?php endif; ?>
    </div>

    <div class="setup-wrap">

        <?php if ($is_first_run && !$_cfg_exists): ?>
        <div class="alert alert-info">👋 Bem-vindo! Configure primeiro a conexão com o banco de dados para começar a usar o sistema.</div>
        <?php endif; ?>
        <?php if ($saved || $db_saved): ?>
        <div class="alert alert-success">✔ Configurações salvas com sucesso!<?php if($db_saved && !$_db_ok): ?> Agora instale o banco de dados abaixo.<?php endif; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error">✖ <?=htmlspecialchars(urldecode($error))?></div>
        <?php endif; ?>
        <?php if ($db_error_msg): ?>
        <div class="alert alert-error">⚠️ <?=$db_error_msg?></div>
        <?php endif; ?>

        <!-- ══ 1. BANCO DE DADOS ══ -->
        <form method="POST" action="pdo/save_settings.php">
        <div class="setup-card">
            <div class="setup-card-header">🗄️ Banco de Dados</div>
            <div class="setup-card-body">
                <div class="setup-row">
                    <div class="setup-field" style="flex:2;min-width:200px;">
                        <label>Servidor (Host)</label>
                        <input type="text" name="db_host" id="db_host"
                               value="<?=htmlspecialchars($_saved_cfg['host'] ?? 'localhost')?>"
                               placeholder="localhost">
                    </div>
                    <div class="setup-field" style="flex:1;min-width:100px;">
                        <label>Porta</label>
                        <input type="number" name="db_port" id="db_port"
                               value="<?=htmlspecialchars($_saved_cfg['port'] ?? 3306)?>"
                               placeholder="3306" min="1" max="65535">
                    </div>
                </div>
                <div class="setup-field">
                    <label>Nome do Banco de Dados</label>
                    <input type="text" name="db_name" id="db_name"
                           value="<?=htmlspecialchars($_saved_cfg['name'] ?? '')?>"
                           placeholder="Ex: u123456789_salao">
                </div>
                <div class="setup-row">
                    <div class="setup-field">
                        <label>Usuário</label>
                        <input type="text" name="db_user" id="db_user"
                               value="<?=htmlspecialchars($_saved_cfg['user'] ?? '')?>"
                               placeholder="Ex: u123456789_admin" autocomplete="username">
                    </div>
                    <div class="setup-field">
                        <label>Senha</label>
                        <div class="pass-wrap">
                            <input type="password" name="db_pass" id="db_pass"
                                   placeholder="Senha do banco" autocomplete="current-password">
                            <span class="pass-toggle" id="pass-toggle">👁</span>
                        </div>
                    </div>
                </div>

                <div class="db-actions">
                    <button type="button" class="btn-outline" id="btn-test">🔌 Testar Conexão</button>
                    <button type="button" class="btn-outline" id="btn-install" <?=!$_db_ok?'disabled':''?>>
                        🛠️ Instalar / Atualizar Banco
                    </button>
                </div>
                <div class="db-status" id="db-status"></div>

                <div class="setup-save-row" style="margin-top:20px;">
                    <button type="submit" class="setup-btn" style="padding:11px 28px;font-size:14px;">
                        💾 Salvar Credenciais
                    </button>
                </div>
            </div>
        </div>
        </form>

        <!-- ══ 2. IDENTIDADE ══ -->
        <form method="POST" action="pdo/save_settings.php" enctype="multipart/form-data">
        <div class="setup-card needs-db <?=!$_db_ok?'locked':''?>">
            <div class="setup-card-header">🏪 Identidade do Salão</div>
            <div class="setup-card-body">
                <div class="setup-field">
                    <label>Nome do Salão</label>
                    <input type="text" name="nome_salao"
                           value="<?=htmlspecialchars($salon_name)?>"
                           placeholder="Ex: Espaço da Beleza Lucia Reis" <?=!$_db_ok?'disabled':''?>>
                </div>
                <div class="setup-field">
                    <label>Localização (cidade, estado)</label>
                    <input type="text" name="local_salao"
                           value="<?=htmlspecialchars($salon_local)?>"
                           placeholder="Ex: Santana do Livramento, RS" <?=!$_db_ok?'disabled':''?>>
                </div>
            </div>
        </div>

        <!-- ══ 3. LOGO ══ -->
        <div class="setup-card needs-db <?=!$_db_ok?'locked':''?>">
            <div class="setup-card-header">🖼️ Logomarca do Salão</div>
            <div class="setup-card-body">
                <div class="logo-upload-area" id="logo-drop">
                    <input type="file" name="logo" id="logo-input" accept="image/*" <?=!$_db_ok?'disabled':''?>>
                    <img id="logo-preview" src="<?=htmlspecialchars($logo_path)?>" alt="Logo atual">
                    <p id="upload-label">Clique ou arraste a imagem da logomarca aqui</p>
                    <p class="upload-hint">Formatos aceitos: JPG, PNG, WebP, SVG — máx. 5 MB</p>
                </div>
            </div>
        </div>

        <!-- ══ 4. CORES ══ -->
        <div class="setup-card needs-db <?=!$_db_ok?'locked':''?>">
            <div class="setup-card-header">🎨 Cores do Sistema</div>
            <div class="setup-card-body">
                <div class="color-row">
                    <div class="color-pick">
                        <label>Cor Primária</label>
                        <div class="color-pick-inner">
                            <input type="color" id="cp-picker" value="<?=$cor_primaria?>" <?=!$_db_ok?'disabled':''?>>
                            <input type="text" class="color-hex" id="cp-hex" name="cor_primaria"
                                   value="<?=$cor_primaria?>" maxlength="7" placeholder="#7a3444" <?=!$_db_ok?'disabled':''?>>
                        </div>
                    </div>
                    <div class="color-pick">
                        <label>Cor de Destaque</label>
                        <div class="color-pick-inner">
                            <input type="color" id="ca-picker" value="<?=$cor_destaque?>" <?=!$_db_ok?'disabled':''?>>
                            <input type="text" class="color-hex" id="ca-hex" name="cor_destaque"
                                   value="<?=$cor_destaque?>" maxlength="7" placeholder="#b06ab3" <?=!$_db_ok?'disabled':''?>>
                        </div>
                    </div>
                </div>
                <div class="color-preview-bar" id="preview-bar">Pré-visualização do gradiente</div>
            </div>
        </div>

        <div class="setup-save-row">
            <button type="submit" class="setup-btn" <?php if(!$_db_ok) echo 'disabled'; ?>>
                💾 Salvar Configurações
            </button>
        </div>
        </form>

        <!-- ══ 5. BACKUP ══ -->
        <div class="setup-card needs-db <?=!$_db_ok?'locked':''?>">
            <div class="setup-card-header">💾 Backup do Banco de Dados</div>
            <div class="setup-card-body">
                <p style="margin:0 0 16px;color:#555;font-size:14px;line-height:1.6;">
                    Gera um arquivo <strong>.sql</strong> completo com todas as tabelas e dados do sistema.
                    Salve-o em um local seguro periodicamente para evitar perda de informações.
                </p>
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <a href="pdo/backup_db.php" class="setup-btn"
                       style="text-decoration:none;padding:12px 28px;font-size:15px;<?=!$_db_ok?'pointer-events:none;opacity:.5;':''?>"
                       <?=!$_db_ok?'aria-disabled="true"':''?>>
                        ⬇️ Baixar Backup SQL
                    </a>
                    <span style="font-size:12px;color:#aaa;">
                        Arquivo: backup_<?=defined('DB_NAME')?DB_NAME:'database'?>_<?=date('Y-m-d')?>.sql
                    </span>
                </div>
            </div>
        </div>

    </div><!-- /.setup-wrap -->

    <script>
    $(document).ready(function(){

        /* ── Toggle de senha ── */
        $('#pass-toggle').click(function(){
            var t = $('#db_pass');
            t.attr('type', t.attr('type') === 'password' ? 'text' : 'password');
            $(this).text(t.attr('type') === 'password' ? '👁' : '🙈');
        });

        /* ── Testar conexão ── */
        function getDbFields(){
            return {
                db_host: $('#db_host').val(),
                db_name: $('#db_name').val(),
                db_user: $('#db_user').val(),
                db_pass: $('#db_pass').val(),
                db_port: $('#db_port').val()
            };
        }
        function showStatus(ok, msg){
            $('#db-status').removeClass('ok error').addClass(ok?'ok':'error').text(msg).show();
        }

        $('#btn-test').click(function(){
            $(this).prop('disabled',true).text('Testando...');
            $.post('pdo/test_db.php', getDbFields(), function(r){
                showStatus(r.ok, r.ok ? '✔ '+r.msg : '✖ '+r.msg);
                if(r.ok) $('#btn-install').prop('disabled', false);
            }, 'json').fail(function(){
                showStatus(false, 'Erro ao contactar o servidor.');
            }).always(function(){
                $('#btn-test').prop('disabled',false).text('🔌 Testar Conexão');
            });
        });

        /* ── Instalar banco ── */
        $('#btn-install').click(function(){
            if(!confirm('Isso criará as tabelas no banco selecionado. Continuar?')) return;
            $(this).prop('disabled',true).text('Instalando...');
            $.post('pdo/install_db.php', {}, function(r){
                showStatus(r.ok, r.ok ? '✔ '+r.msg : '✖ '+r.msg);
            }, 'json').fail(function(){
                showStatus(false, 'Erro ao executar a instalação.');
            }).always(function(){
                $('#btn-install').prop('disabled',false).text('🛠️ Instalar / Atualizar Banco');
            });
        });

        /* ── Color pickers ── */
        function syncPicker(pickerId, hexId){
            $(pickerId).on('input', function(){ $(hexId).val(this.value); updatePreview(); });
            $(hexId).on('input', function(){
                if(/^#[0-9a-fA-F]{6}$/.test(this.value)){ $(pickerId).val(this.value); updatePreview(); }
            });
        }
        syncPicker('#cp-picker','#cp-hex');
        syncPicker('#ca-picker','#ca-hex');
        $('#ca-picker,#ca-hex').on('input', updatePreview);

        function updatePreview(){
            var p = $('#cp-hex').val() || '<?=$cor_primaria?>';
            var a = $('#ca-hex').val() || '<?=$cor_destaque?>';
            var g = 'linear-gradient(135deg,'+p+','+a+')';
            $('#preview-bar, .setup-card-header, .setup-page-header, .setup-btn').css('background', g);
        }

        /* ── Preview logo ── */
        $('#logo-input').on('change', function(){
            var f = this.files[0];
            if(!f) return;
            var r = new FileReader();
            r.onload = function(e){ $('#logo-preview').attr('src',e.target.result); $('#upload-label').text(f.name); };
            r.readAsDataURL(f);
        });
    });
    </script>
</body>
</html>
