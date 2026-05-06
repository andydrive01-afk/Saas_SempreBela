<?php
class settings_DAO {
    public function get_all($connection) {
        try {
            $rows = $connection->query("SELECT chave, valor FROM configuracoes")->fetchAll(PDO::FETCH_OBJ);
            $settings = [];
            foreach ($rows as $r) {
                $settings[$r->chave] = $r->valor;
            }
            return $settings;
        } catch (PDOException $e) {
            return [];
        }
    }
    public function set($key, $value, $connection) {
        try {
            $stmt = $connection->prepare("INSERT INTO configuracoes (chave, valor) VALUES (:k, :v)
                ON DUPLICATE KEY UPDATE valor = :v2");
            $stmt->bindValue(':k',  $key);
            $stmt->bindValue(':v',  $value);
            $stmt->bindValue(':v2', $value);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
