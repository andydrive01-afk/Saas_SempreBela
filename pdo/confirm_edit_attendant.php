<?php
session_start(); $_n=['agente'=>1,'master'=>2,'admin'=>3]; if(!isset($_SESSION['user_id'])||($_n[$_SESSION['nivel']]??0)<2){header('Location: ../login.php?erro=sem_permissao');exit;}
include_once ("connection.php");
include_once ("classes/attendant.php");
include_once ("DAO/attendant_DAO.php");

if(isset($_POST['id'], $_POST['name']) && $_POST['id'] != "" && $_POST['name'] != ""){

    $id = $_POST['id'];

    $c = new connection();
    $conn = $c->connect();

    $a = new attendant();
    $a->setName(trim($_POST['name']));

    $dao = new attendant_DAO();
    $result = $dao->edit_attendant($id, $a, $conn);

    if($result == true){
        $message = "Sucesso! O atendente foi alterado com êxito.";
    } else {
        $message = "Erro! Verifique se o servidor foi inicializado corretamente.";
    }
} else {
    $message = "Erro! As informações enviadas não são válidas, por favor tente novamente.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../css/fonts.css" rel="stylesheet">
    <link href="../css/main.css" rel="stylesheet" />
    <script src="../js/lord-icon.js"></script>
    <link rel="icon" href="../img/logo.png">
    <title>Lucia Reis | Atendentes</title>
</head>
<body class="list-main-section-form" id="background-type-attendant">
    <header>
        <div class="return">
            <a href="../attendants.php" title="Voltar" target="_self" rel="prev">
                <lord-icon src="../css/icons/return.json"
                    trigger="hover"
                    delay="1000"
                    colors="primary:#000,secondary:#ffffff"
                    style="width:30px;height:auto;padding-bottom:10px;padding-left:5px;">
                </lord-icon>
            </a>
        </div>
        <div class="title-form" id="attendant-title">
            <div class="main-text-title">
                <h1>Edição de Atendente</h1>
            </div>
        </div>
    </header>
    <main>
        <section class="list-out-form">
            <div class="form-style">
                <p><?=$message?></p>
                <a href="../attendants.php" target="_self" rel="prev">Voltar aos atendentes</a>
            </div>
        </section>
    </main>
    <footer>
        <p>Espaço da Beleza Lucia Reis - Santana do Livramento, RS
        <lord-icon src="../css/icons/local.json"
            trigger="loop"
            delay="1000"
            colors="primary:#ffffff,secondary:#ffffff"
            style="width:30px;height:auto;padding-bottom:10px;">
        </lord-icon>
        | &copy; Todos os direitos reservados.</p>
    </footer>
</body>
</html>
