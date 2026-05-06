<?php
include_once 'inc/auth.php';
include_once 'inc/settings.php';
include_once ('pdo/connection.php');
include_once ('pdo/DAO/costumer_DAO.php');
include_once ('pdo/DAO/treatment_DAO.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$c = new connection();
$conn = $c->connect();

$c_dao = new costumer_DAO();
$costumer_data = $c_dao->costumer_info($id, $conn);

$t_dao = new treatment_DAO();
$treatments = $t_dao->client_treatments($id, $conn);
$summary    = $t_dao->client_summary($id, $conn);

$costumer = null;
if($costumer_data && count($costumer_data) > 0){
    $costumer = $costumer_data[0];
}

$months_pt = [
    1=>'Jan', 2=>'Fev', 3=>'Mar', 4=>'Abr', 5=>'Mai', 6=>'Jun',
    7=>'Jul', 8=>'Ago', 9=>'Set', 10=>'Out', 11=>'Nov', 12=>'Dez',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/fonts.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet" />
    <link href="css/theme.php" rel="stylesheet">
    <script src="js/lord-icon.js"></script>
    <script src="js/jquery-3.5.1.min.js"></script>
    <link rel="icon" href="<?=$salon_icon?>">
    <title><?=$salon_name?> | Histórico do Cliente</title>
</head>
<body class="list-main-section">
    <header>
        <div class="return">
            <a href="costumers.php" title="Voltar" target="_self" rel="prev">
                <lord-icon src="css/icons/return.json"
                    trigger="hover"
                    delay="1000"
                    colors="primary:#000,secondary:#ffffff"
                    style="width:30px;height:auto;padding-bottom:10px;padding-left:5px;">
                </lord-icon>
            </a>
        </div>
        <div class="title" id="costumer-title">
            <div class="main-text-title">
                <h1>Histórico do Cliente</h1>
            </div>
        </div>
    </header>
    <main>
        <section class="list-out">

        <?php if(!$costumer): ?>
            <p style="padding:20px;">Cliente não encontrado.</p>
        <?php else: ?>

            <!-- Client info card -->
            <div class="client-profile-card">
                <div class="client-profile-avatar">
                    <?=mb_strtoupper(mb_substr($costumer->nome, 0, 1, 'UTF-8'), 'UTF-8');?>
                </div>
                <div class="client-profile-info">
                    <h2><?=htmlspecialchars($costumer->nome);?></h2>
                    <p>
                        <?php if($costumer->tel): ?>
                            <span>📞 <?=htmlspecialchars($costumer->tel);?></span>
                        <?php endif; ?>
                        <?php if($costumer->data_nasc && $costumer->data_nasc !== '0000-00-00'): ?>
                            &nbsp;&nbsp;<span>🎂 <?=date('d/m/Y', strtotime($costumer->data_nasc));?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="monthly-summary-totals">
                <div class="summary-card">
                    <span class="summary-card-label">Total de atendimentos</span>
                    <span class="summary-card-value"><?=$summary ? $summary->total_atendimentos : 0;?></span>
                </div>
                <div class="summary-card">
                    <span class="summary-card-label">Total gasto</span>
                    <span class="summary-card-value">R$ <?php
                        $total_gasto = ($summary && $summary->total_gasto !== null) ? (float)$summary->total_gasto : 0.0;
                        echo number_format($total_gasto, 2, ',', '.');
                    ?></span>
                </div>
                <div class="summary-card">
                    <span class="summary-card-label">Média por visita</span>
                    <span class="summary-card-value">R$ <?php
                        $total_count = ($summary && $summary->total_atendimentos > 0) ? (int)$summary->total_atendimentos : 0;
                        $avg = $total_count > 0 ? $total_gasto / $total_count : 0.0;
                        echo number_format($avg, 2, ',', '.');
                    ?></span>
                </div>
                <?php if($summary && $summary->ultimo_atendimento): ?>
                <div class="summary-card">
                    <span class="summary-card-label">Última visita</span>
                    <span class="summary-card-value" style="font-size:20px;"><?=date('d/m/Y', strtotime($summary->ultimo_atendimento));?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Search bar -->
            <div class="search-bar-wrap">
                <input type="text" id="history-search" placeholder="Filtrar por serviço, atendente ou data...">
            </div>

            <!-- Treatments table -->
            <div class="data-table-large" id="data-table-history">
                <table border="0">
                    <tr>
                        <th>Data</th>
                        <th>Atendente</th>
                        <th>Serviços Realizados</th>
                        <th>Pagamento</th>
                        <th>Promoção</th>
                        <th>Valor</th>
                    </tr>
                    <?php if(!$treatments || count($treatments) == 0): ?>
                        </table>
                        <p>Nenhum atendimento registrado para este cliente.</p>
                    <?php else: foreach($treatments as $t):
                        if($t->promocao_percent != 0){
                            $promo = $t->promocao_percent.'% OFF';
                        } elseif($t->promocao_valor != 0){
                            $promo = 'R$'.number_format($t->promocao_valor,2,',').' OFF';
                        } else {
                            $promo = '—';
                        }
                        $atendente = $t->atendente_nome ?: '—';
                        $servicos  = $t->servicos_lista ?: '—';
                        $search_text = strtolower(
                            date('d/m/Y', strtotime($t->data_atendimento)).' '.
                            $atendente.' '.$servicos.' '.$t->metodo
                        );
                    ?>
                    <tr data-search="<?=htmlspecialchars($search_text);?>">
                        <td><?=date('d/m/Y', strtotime($t->data_atendimento));?></td>
                        <td><?=htmlspecialchars($atendente);?></td>
                        <td class="services-cell"><?=htmlspecialchars($servicos);?></td>
                        <td><?=htmlspecialchars($t->metodo);?></td>
                        <td><?=$promo;?></td>
                        <td>R$ <?=number_format($t->valor_atendimento, 2, ',', '');?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </table>
            </div>

        <?php endif; ?>
        </section>
    </main>
    <footer>
        <p><?=$salon_name?> - <?=$salon_local?>
        <lord-icon src="css/icons/local.json"
            trigger="loop"
            delay="1000"
            colors="primary:#ffffff,secondary:#ffffff"
            style="width:30px;height:auto;padding-bottom:10px;">
        </lord-icon>
        | &copy; Todos os direitos reservados.</p>
    </footer>
    <script>
        $(document).ready(function(){
            $('#history-search').on('input', function(){
                var term = $(this).val().toLowerCase().trim();
                $('#data-table-history table tr:not(:first-child)').each(function(){
                    var text = $(this).data('search') || '';
                    $(this).toggle(!term || text.indexOf(term) !== -1);
                });
            });
        });
    </script>
</body>
</html>
