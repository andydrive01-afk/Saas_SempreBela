<?php
include_once ("connection.php");
include_once ("DAO/attendant_DAO.php");

$id = $_GET['id'];

$c = new connection();
$conn = $c->connect();

$dao = new attendant_DAO();
$dao->attendant_delete($id, $conn);

header("location:../attendants.php");
