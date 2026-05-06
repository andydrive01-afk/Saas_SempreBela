<?php
include_once 'inc/settings.php';
include_once ('pdo/connection.php');
include_once ('pdo/DAO/treatment_DAO.php');

$c = new connection();
$conn = $c->connect();

$dao = new treatment_DAO();
$stmt = $dao->monthly_summary($conn);

$months_pt = [
    1  => 'Janeiro',   2  => 'Fevereiro', 3  => 'Março',
    4  => 'Abril',     5  => 'Maio',      6  => 'Junho',
    7  => 'Julho',     8  => 'Agosto',    9  => 'Setembro',
    10 => 'Outubro',   11 => 'Novembro',  12 => 'Dezembro',
];

$grand_total_count   = 0;
$grand_total_revenue = 0;
if($stmt){
    foreach($stmt as $row){
        $grand_total_count   += $row->total_atendimentos;
        $grand_total_revenue += $row->receita_total;
    }
}
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
    <title><?=$salon_name?> | Resumo Mensal</title>
</head>
<body class="list-main-section">
    <header>
        <div class="return">
            <a href="treatments.php" title="Voltar" target="_self" rel="prev">
                <lord-icon src="css/icons/return.json"
                    trigger="hover"
                    delay="1000"
                    colors="primary:#000,secondary:#ffffff"
                    style="width:30px;height:auto;padding-bottom:10px;padding-left:5px;">
                </lord-icon>
            </a>
        </div>
        <div class="title" id="treatment-title">
            <div class="main-text-title">
                <h1>Resumo Mensal</h1>
            </div>
        </div>
    </header>
    <main>
        <section class="list-out">

            <div class="monthly-summary-totals">
                <div class="summary-card">
                    <span class="summary-card-label">Total de atendimentos</span>
                    <span class="summary-card-value"><?=$grand_total_count;?></span>
                </div>
                <div class="summary-card">
                    <span class="summary-card-label">Receita total acumulada</span>
                    <span class="summary-card-value">R$ <?=number_format($grand_total_revenue, 2, ',', '.');?></span>
                </div>
            </div>

            <div class="search-bar-wrap">
                <input type="text" id="month-search" placeholder="Filtrar por mês ou ano...">
            </div>

            <div class="data-table" id="data-table-monthly">
                <table border="0">
                    <tr>
                        <th>Mês</th>
                        <th>Ano</th>
                        <th>Nº de Atendimentos</th>
                        <th>Receita do Mês</th>
                        <th>Média por Atendimento</th>
                    </tr>
                    <?php if($stmt == null || count($stmt) == 0): ?>
                        </table>
                        <p>Nenhum atendimento registrado ainda.</p>
                    <?php else: ?>
                        <?php foreach($stmt as $row):
                            $avg = $row->total_atendimentos > 0
                                ? $row->receita_total / $row->total_atendimentos
                                : 0;
                        ?>
                        <tr data-text="<?=strtolower($months_pt[$row->mes]);?> <?=$row->ano;?>">
                            <td><?=$months_pt[$row->mes];?></td>
                            <td><?=$row->ano;?></td>
                            <td><?=$row->total_atendimentos;?></td>
                            <td>R$ <?=number_format($row->receita_total, 2, ',', '.');?></td>
                            <td>R$ <?=number_format($avg, 2, ',', '.');?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="summary-table-footer">
                            <td colspan="2"><strong>Total geral</strong></td>
                            <td><strong><?=$grand_total_count;?></strong></td>
                            <td><strong>R$ <?=number_format($grand_total_revenue, 2, ',', '.');?></strong></td>
                            <td><strong>R$ <?=number_format($grand_total_count > 0 ? $grand_total_revenue / $grand_total_count : 0, 2, ',', '.');?></strong></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
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
            $('#month-search').on('input', function(){
                var term = $(this).val().toLowerCase().trim();
                $('#data-table-monthly table tr:not(:first-child):not(.summary-table-footer)').each(function(){
                    var text = $(this).data('text') || '';
                    $(this).toggle(!term || text.indexOf(term) !== -1);
                });
            });
        });
    </script>
</body>
</html>
