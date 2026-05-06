<?php
session_start(); $_n=['agente'=>1,'master'=>2,'admin'=>3]; if(!isset($_SESSION['user_id'])||($_n[$_SESSION['nivel']]??0)<2){header('Location: ../login.php?erro=sem_permissao');exit;}
include_once ("connection.php");
include_once ("classes/attendant.php");
include_once ("DAO/attendant_DAO.php");

$c = new connection();
$conn = $c->connect();

$a = new attendant();
$a->setName(trim($_POST['name']));

$dao = new attendant_DAO();
$result = $dao->insert_attendant($a, $conn);

header("location:../attendants.php");
