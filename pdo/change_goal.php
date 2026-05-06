<?php
session_start(); $_n=['agente'=>1,'master'=>2,'admin'=>3]; if(!isset($_SESSION['user_id'])||($_n[$_SESSION['nivel']]??0)<2){header('Location: ../login.php?erro=sem_permissao');exit;}

    include_once ("connection.php");
    include_once ("classes/financial.php");
    include_once ("DAO/financial_DAO.php");

    if(isset($_POST['new_goal'])&& $_POST['new_goal'] != ""){

        $c = new connection();
        $conn = $c->connect();

        $f = new financial();
        $f->setWeeklyGoal($_POST['new_goal']);

        $edit = new financial_DAO();
        $stmt = $edit->edit_goal($f, $conn);

        header("location: ../financial_data.php");
    }
    else{
        header("location: ../financial_data.php");
    }
