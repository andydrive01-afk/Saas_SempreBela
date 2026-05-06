<?php
include_once 'pdo/connection.php';
include_once 'pdo/DAO/settings_DAO.php';

$c    = new connection();
$conn = $c->connect();
$dao  = new settings_DAO();
$s    = $dao->get_all($conn);

$salon_name   = $s['nome_salao']   ?? 'Espaço da Beleza Lucia Reis';
$salon_local  = $s['local_salao']  ?? 'Santana do Livramento, RS';
$cor_primaria = $s['cor_primaria'] ?? '#7a3444';
$cor_destaque = $s['cor_destaque'] ?? '#b06ab3';
$logo_path    = $s['logo_path']    ?? 'img/logo-full.png';

$saved = isset($_GET['saved']) && $_GET['saved'] == '1';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/fonts.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/theme.php" rel="stylesheet">
    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="js/lord-icon.js"></script>
    <link rel="icon" href="<?=$logo_path?>">
    <title>Configurações do Salão</title>
    <style>
        body { background: #f4f0f4; font-family: 'Roboto', sans-serif; margin: 0; }

        .setup-page-header {
            background: linear-gradient(135deg, <?=$cor_primaria?>, <?=$cor_destaque?>);
            color: #fff;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
            box-shadow: 0 2px 8px rgba(0,0,0,.18);
        }
        .setup-page-header h1 { margin:0; font-size:22px; font-weight:700; letter-spacing:.3px; }
        .setup-back { color:#fff; text-decoration:none; font-size:14px; display:flex; align-items:center; gap:6px; }
        .setup-back:hover { opacity:.8; }

        .setup-wrap {
            max-width: 780px;
            margin: 36px auto 60px;
            display: flex;
            flex-direction: column;
            gap: 28px;
            padding: 0 16px;
        }

        .setup-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .setup-card-header {
            background: linear-gradient(135deg, <?=$cor_primaria?>, <?=$cor_destaque?>);
            color: #fff;
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .setup-card-body { padding: 24px; }

        .setup-field { margin-bottom: 20px; }
        .setup-field:last-child { margin-bottom: 0; }
        .setup-field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .setup-field input[type="text"],
        .setup-field input[type="url"] {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 14px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color .2s;
            outline: none;
        }
        .setup-field input[type="text"]:focus,
        .setup-field input[type="url"]:focus {
            border-color: <?=$cor_primaria?>;
        }

        /* Color pickers */
        .color-row { display: flex; gap: 24px; flex-wrap: wrap; }
        .color-pick { flex: 1; min-width: 180px; }
        .color-pick label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:8px; text-transform:uppercase; letter-spacing:.4px; }
        .color-pick-inner { display:flex; align-items:center; gap:12px; }
        .color-pick input[type="color"] {
            width: 52px; height: 52px;
            border: none; border-radius: 10px;
            cursor: pointer; padding: 2px;
            background: #f0f0f0;
        }
        .color-hex {
            flex: 1;
            padding: 9px 12px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: monospace;
            outline: none;
        }
        .color-preview-bar {
            margin-top: 16px;
            height: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, <?=$cor_primaria?>, <?=$cor_destaque?>);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .3px;
            transition: background .3s;
        }

        /* Logo upload */
        .logo-upload-area {
            border: 2px dashed #ccc;
            border-radius: 12px;
            padding: 28px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            position: relative;
        }
        .logo-upload-area:hover { border-color: <?=$cor_primaria?>; background: #faf5fa; }
        .logo-upload-area input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width:100%; height:100%;
        }
        .logo-upload-area img#logo-preview {
            max-height: 100px;
            max-width: 100%;
            border-radius: 8px;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .logo-upload-area p { margin: 0; color: #888; font-size: 14px; }
        .logo-upload-area .upload-hint { margin-top: 6px; font-size: 12px; color: #bbb; }

        /* Save button */
        .setup-save-row { display:flex; justify-content:flex-end; gap:12px; margin-top:4px; }
        .setup-btn {
            padding: 13px 36px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            background: linear-gradient(135deg, <?=$cor_primaria?>, <?=$cor_destaque?>);
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
            transition: opacity .2s, transform .1s;
        }
        .setup-btn:hover { opacity:.88; transform:translateY(-1px); }
        .setup-btn:active { transform:translateY(0); }

        /* Alerts */
        .alert {
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #eafaf1; color: #1a7a45; border: 1.5px solid #a8e6c0; }
        .alert-error   { background: #fdf0f0; color: #b91c1c; border: 1.5px solid #f5b8b8; }
    </style>
</head>
<body>
    <div class="setup-page-header" id="setup-header">
        <h1>⚙️ Configurações do Salão</h1>
        <a href="index.php" class="setup-back">← Voltar ao início</a>
    </div>

    <form class="setup-wrap" method="POST" action="pdo/save_settings.php" enctype="multipart/form-data">

        <?php if ($saved): ?>
        <div class="alert alert-success">✔ Configurações salvas com sucesso! As alterações já estão aplicadas em todo o sistema.</div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error">✖ <?=htmlspecialchars(urldecode($error))?></div>
        <?php endif; ?>

        <!-- 1. Identidade -->
        <div class="setup-card">
            <div class="setup-card-header">🏪 Identidade do Salão</div>
            <div class="setup-card-body">
                <div class="setup-field">
                    <label>Nome do Salão</label>
                    <input type="text" name="nome_salao" value="<?=htmlspecialchars($salon_name)?>"
                           placeholder="Ex: Espaço da Beleza Lucia Reis">
                </div>
                <div class="setup-field">
                    <label>Localização (cidade, estado)</label>
                    <input type="text" name="local_salao" value="<?=htmlspecialchars($salon_local)?>"
                           placeholder="Ex: Santana do Livramento, RS">
                </div>
            </div>
        </div>

        <!-- 2. Logo -->
        <div class="setup-card">
            <div class="setup-card-header">🖼 Logomarca do Salão</div>
            <div class="setup-card-body">
                <div class="logo-upload-area" id="logo-drop">
                    <input type="file" name="logo" id="logo-input" accept="image/*">
                    <img id="logo-preview" src="<?=htmlspecialchars($logo_path)?>" alt="Logo atual">
                    <p id="upload-label">Clique ou arraste a imagem da logomarca aqui</p>
                    <p class="upload-hint">Formatos aceitos: JPG, PNG, WebP, SVG — máx. 5 MB</p>
                </div>
            </div>
        </div>

        <!-- 3. Cores -->
        <div class="setup-card">
            <div class="setup-card-header">🎨 Cores do Sistema</div>
            <div class="setup-card-body">
                <div class="color-row">
                    <div class="color-pick">
                        <label>Cor Primária</label>
                        <div class="color-pick-inner">
                            <input type="color" id="cp-picker" value="<?=$cor_primaria?>">
                            <input type="text" class="color-hex" id="cp-hex" name="cor_primaria"
                                   value="<?=$cor_primaria?>" maxlength="7" placeholder="#7a3444">
                        </div>
                    </div>
                    <div class="color-pick">
                        <label>Cor de Destaque</label>
                        <div class="color-pick-inner">
                            <input type="color" id="ca-picker" value="<?=$cor_destaque?>">
                            <input type="text" class="color-hex" id="ca-hex" name="cor_destaque"
                                   value="<?=$cor_destaque?>" maxlength="7" placeholder="#b06ab3">
                        </div>
                    </div>
                </div>
                <div class="color-preview-bar" id="preview-bar">
                    Pré-visualização do gradiente
                </div>
            </div>
        </div>

        <!-- Save -->
        <div class="setup-save-row">
            <button type="submit" class="setup-btn">💾 Salvar Configurações</button>
        </div>

    </form>

    <script>
    $(document).ready(function(){

        /* ── Sincronizar color picker ↔ hex input ── */
        function syncColor(picker, hex, other_picker, other_hex) {
            $(picker).on('input', function(){
                $(hex).val(this.value);
                updatePreview();
            });
            $(hex).on('input', function(){
                var v = this.value;
                if (/^#[0-9a-fA-F]{6}$/.test(v)) {
                    $(picker).val(v);
                    updatePreview();
                }
            });
        }
        syncColor('#cp-picker','#cp-hex');
        syncColor('#ca-picker','#ca-hex');

        $('#ca-picker').on('input', updatePreview);
        $('#ca-hex').on('input', updatePreview);

        function updatePreview(){
            var p = $('#cp-hex').val() || '<?=$cor_primaria?>';
            var a = $('#ca-hex').val() || '<?=$cor_destaque?>';
            $('#preview-bar').css('background', 'linear-gradient(135deg,'+p+','+a+')');
            $('.setup-card-header').css('background', 'linear-gradient(135deg,'+p+','+a+')');
            $('.setup-page-header').css('background', 'linear-gradient(135deg,'+p+','+a+')');
            $('.setup-btn').css('background', 'linear-gradient(135deg,'+p+','+a+')');
        }

        /* ── Preview logo ── */
        $('#logo-input').on('change', function(){
            var file = this.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e){
                $('#logo-preview').attr('src', e.target.result).show();
                $('#upload-label').text(file.name);
            };
            reader.readAsDataURL(file);
        });
    });
    </script>
</body>
</html>
