<?php
include_once 'inc/auth.php';
include_once 'inc/settings.php';
include_once ('pdo/connection.php');
include_once ('pdo/DAO/attendant_DAO.php');

$c = new connection();
$conn = $c->connect();

$select = new attendant_DAO();
$stmt = $select->attendants_list($conn);
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
    <title><?=$salon_name?> | Atendentes</title>
</head>
<body class="list-main-section">
    <header>
        <div class="return">
            <a href="index.php" title="Voltar" target="_self" rel="prev">
                <lord-icon src="css/icons/return.json"
                    trigger="hover"
                    delay="1000"
                    colors="primary:#000,secondary:#ffffff"
                    style="width:30px;height:auto;padding-bottom:10px;padding-left:5px;">
                </lord-icon>
            </a>
        </div>
        <div class="title" id="attendant-title">
            <div class="main-text-title">
                <h1>Atendentes</h1>
            </div>
            <div class="new-item">
                <a href="new_attendant.php" title="Novo Atendente" target="_self" rel="next">
                    <lord-icon src="css/icons/plus.json"
                    trigger="none"
                    colors="primary:#ffffff,secondary:#ffffff"
                    style="width:50px;height:auto">
                    </lord-icon>
                </a>
            </div>
        </div>
    </header>
    <main>
        <section class="list-out">
            <div class="data-table" id="data-table-attendant">
                <table border='0'>
                    <tr><th>Nome</th><th>Opções</th></tr>

                    <?php
                        if($stmt == null || count($stmt) == 0) {
                    ?>
                            </table>
                            <p>Nenhum atendente foi encontrado.</p>
                    <?php
                        } else {
                            foreach($stmt as $a) {
                    ?>
                                <tr>
                                    <td><?=$a->nome;?></td>
                                    <td>
                                        <a id="edit-<?=$a->id;?>" href="pdo/edit_attendant.php?id=<?=$a->id;?>" title='Editar'>
                                            <lord-icon id="edit-anim-<?=$a->id;?>" src="css/icons/edit.json"
                                            trigger="none"
                                            colors="primary:#000,secondary:#000"
                                            style="width:40px;height:auto">
                                            </lord-icon>
                                        </a>|
                                        <a id="delete-<?=$a->id;?>" href="pdo/delete_attendant.php?id=<?=$a->id;?>" title='Deletar'
                                           onclick="return confirm('Tem certeza que deseja excluir o atendente <?=htmlspecialchars($a->nome);?>?')">
                                            <lord-icon id="delete-anim-<?=$a->id;?>" src="css/icons/delete.json"
                                            trigger="none"
                                            colors="primary:#000,secondary:#000"
                                            style="width:40px;height:auto">
                                            </lord-icon>
                                        </a>
                                        <script>
                                            $(document).ready(function(){
                                                $('#edit-<?=$a->id;?>').mouseover(function(){
                                                    $('#edit-anim-<?=$a->id;?>').attr("trigger", "loop");
                                                });
                                                $('#edit-<?=$a->id;?>').mouseleave(function(){
                                                    $('#edit-anim-<?=$a->id;?>').attr("trigger", "none");
                                                });
                                                $('#delete-<?=$a->id;?>').mouseover(function(){
                                                    $('#delete-anim-<?=$a->id;?>').attr("trigger", "loop");
                                                });
                                                $('#delete-<?=$a->id;?>').mouseleave(function(){
                                                    $('#delete-anim-<?=$a->id;?>').attr("trigger", "none");
                                                });
                                            });
                                        </script>
                                    </td>
                                </tr>
                    <?php
                            }
                        }
                    ?>
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
</body>
</html>
