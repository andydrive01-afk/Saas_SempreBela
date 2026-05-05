<?php
class connection{
    protected $pdo;

    function connect(){ // Faz a conexão com o banco de datos
        $this->pdo = new PDO("mysql:unix_socket=/tmp/mysql.sock;dbname=database", "root", "");
        return $this->pdo;
    }
}
