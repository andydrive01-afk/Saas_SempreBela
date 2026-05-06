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
            // Detect driver to use the right upsert syntax
            $driver = '';
            try { $driver = strtolower($connection->getAttribute(PDO::ATTR_DRIVER_NAME)); } catch (Exception $e) {}

            if ($driver === 'sqlite') {
                $sql = "INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES (:k, :v)";
                $stmt = $connection->prepare($sql);
                $stmt->bindValue(':k', $key);
                $stmt->bindValue(':v', $value);
            } elseif ($driver === 'pgsql') {
                $sql = "INSERT INTO configuracoes (chave, valor) VALUES (:k, :v)
                        ON CONFLICT (chave) DO UPDATE SET valor = :v2";
                $stmt = $connection->prepare($sql);
                $stmt->bindValue(':k',  $key);
                $stmt->bindValue(':v',  $value);
                $stmt->bindValue(':v2', $value);
            } else {
                // MySQL / MariaDB
                $sql = "INSERT INTO configuracoes (chave, valor) VALUES (:k, :v)
                        ON DUPLICATE KEY UPDATE valor = :v2";
                $stmt = $connection->prepare($sql);
                $stmt->bindValue(':k',  $key);
                $stmt->bindValue(':v',  $value);
                $stmt->bindValue(':v2', $value);
            }
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
