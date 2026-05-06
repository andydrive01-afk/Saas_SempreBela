<?php
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-store');

include_once __DIR__ . '/../pdo/connection.php';
include_once __DIR__ . '/../pdo/DAO/settings_DAO.php';

$c    = new connection();
$conn = $c->connect();
$dao  = new settings_DAO();
$s    = $dao->get_all($conn);

$p = $s['cor_primaria'] ?? '#7a3444';
$a = $s['cor_destaque'] ?? '#b06ab3';

function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
}

$pr = hexToRgb($p);
$ar = hexToRgb($a);
$p_dark = sprintf('#%02x%02x%02x', max(0,$pr[0]-20), max(0,$pr[1]-20), max(0,$pr[2]-20));
?>

:root {
    --cor-primaria: <?=$p?>;
    --cor-destaque: <?=$a?>;
    --cor-gradiente: linear-gradient(135deg, <?=$p?>, <?=$a?>);
    --cor-gradiente-rev: linear-gradient(135deg, <?=$a?>, <?=$p?>);
}

/* ── Cabeçalho e rodapé ── */
.menu, header.menu,
#header-container { background: <?=$p?>; }

footer { background: <?=$p?>; }

/* ── Botões e ações ── */
.button a,
.new-item a,
.button-confirm,
button[type="submit"],
.setup-btn { background: linear-gradient(135deg, <?=$p?>, <?=$a?>) !important; }

.button a:hover,
.new-item a:hover { background: linear-gradient(135deg, <?=$a?>, <?=$p?>) !important; }

/* ── Cabeçalhos de seção (header de lista) ── */
#treatment-title,
#costumer-title,
#service-title,
#product-title,
#attendant-title,
#financial-title,
#summary-title,
.list-main-section header { background: linear-gradient(135deg, <?=$p?>, <?=$a?>); }

/* ── Cabeçalhos de tabela ── */
.data-table th,
.data-table-large th,
#data-table-monthly th,
#data-table-history th { background: linear-gradient(135deg, <?=$p?>, <?=$a?>); }

/* ── Cards de resumo ── */
.summary-card { background: linear-gradient(135deg, <?=$p?>, <?=$a?>); }

/* ── Perfil do cliente ── */
.client-profile-avatar { background: linear-gradient(135deg, <?=$p?>, <?=$a?>); }

/* ── Links e destaques ── */
a:not(.button a):not(.new-item a) { color: <?=$p?>; }

/* ── Select2 foco ── */
.select2-container--default .select2-selection--single:focus,
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: <?=$p?>;
    box-shadow: 0 0 0 2px rgba(<?=$pr[0]?>,<?=$pr[1]?>,<?=$pr[2]?>,0.2);
}

/* ── Barra de notificação ── */
#popup-notify { border-left: 4px solid <?=$a?>; }

/* ── Rodapé de tabela de sumário ── */
.summary-table-footer td { border-top: 2px solid <?=$a?>; }

/* ── Clear filter button ── */
.filter-clear { background: linear-gradient(135deg, <?=$p?>, <?=$a?>) !important; }

/* ── Input focus ── */
input[type="text"]:focus,
input[type="number"]:focus,
input[type="date"]:focus,
select:focus,
textarea:focus {
    border-color: <?=$p?>;
    outline: none;
    box-shadow: 0 0 0 2px rgba(<?=$pr[0]?>,<?=$pr[1]?>,<?=$pr[2]?>,0.15);
}
