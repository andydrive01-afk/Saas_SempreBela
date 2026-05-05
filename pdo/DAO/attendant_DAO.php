<?php
class attendant_DAO {
    public function insert_attendant($attendant, $connection) {
        include_once ("classes/attendant.php");
        try {
            $stmt = $connection->prepare("INSERT INTO atendentes(nome) VALUES(:nome)");
            $stmt->bindValue(":nome", $attendant->getName());
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function attendants_list($connection) {
        try {
            $stmt = $connection->query("SELECT * FROM atendentes ORDER BY nome ASC")->fetchAll(PDO::FETCH_OBJ);
            return $stmt;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function attendant_delete($id, $connection) {
        try {
            $stmt = $connection->prepare("DELETE FROM atendentes WHERE id = :id");
            $stmt->bindValue(":id", $id);
            $stmt->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function attendant_info($id, $connection) {
        try {
            $stmt = $connection->query("SELECT * FROM atendentes WHERE id = '$id'")->fetchAll(PDO::FETCH_OBJ);
            return $stmt;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function edit_attendant($id, $attendant, $connection) {
        include_once ("classes/attendant.php");
        try {
            $stmt = $connection->prepare("UPDATE atendentes SET nome = ? WHERE id = ?");
            $stmt->bindValue(1, $attendant->getName());
            $stmt->bindValue(2, $id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function attendant_name($id, $connection) {
        try {
            $stmt = $connection->query("SELECT nome FROM atendentes WHERE id = '$id'")->fetchAll(PDO::FETCH_OBJ);
            return $stmt;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return null;
        }
    }
}
