<?php
session_start(); $_n=['agente'=>1,'master'=>2,'admin'=>3]; if(!isset($_SESSION['user_id'])||($_n[$_SESSION['nivel']]??0)<2){header('Location: ../login.php?erro=sem_permissao');exit;}
    include_once ("connection.php");
    include_once ("DAO/product_DAO.php");
    include_once ("DAO/treatment_DAO.php");

    $id = $_GET['id'];

    $c = new connection();
    $conn = $c->connect();

    $check = new treatment_DAO();
    if($check->product_in_use($id, $conn)){
        $message = "Não é possível excluir este produto pois ele está vinculado a atendimentos já finalizados.";
    } else {
        $p = new product_DAO();
        $p->product_delete($id, $conn);
        header("location:../products.php");
        exit;
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
    <title>Lucia Reis | Estoque</title>
</head>
<body class="list-main-section-form" id="background-type-product">
    <header>
        <div class="return">
            <a href="../products.php" title="Voltar" target="_self" rel="prev">
                <lord-icon src="../css/icons/return.json"
                    trigger="hover"
                    delay="1000"
                    colors="primary:#000,secondary:#ffffff"
                    style="width:30px;height:auto;padding-bottom:10px;padding-left:5px;">
                </lord-icon>
            </a>
        </div>
        <div class="title-form" id="product-title">
            <div class="main-text-title">
                <h1>Estoque</h1>
            </div>
        </div>
    </header>
    <main>
        <section class="list-out-form">
            <div class="form-style">
                <p style="color:red;"><?=$message?></p>
                <br>
                <a href="../products.php">Voltar para Estoque</a>
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
