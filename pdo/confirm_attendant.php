<?php
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
