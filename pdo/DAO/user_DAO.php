<?php
class user_DAO {

    public function create($nome, $login, $senha, $nivel, $pdo) {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, login, senha_hash, nivel) VALUES (?,?,?,?)");
        $stmt->execute([$nome, $login, $hash, $nivel]);
        return (int)$pdo->lastInsertId();
    }

    public function find_by_login($login, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$login]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function find_by_id($id, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function count_by_nivel($nivel, $pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nivel = ?");
        $stmt->execute([$nivel]);
        return (int)$stmt->fetchColumn();
    }

    public function list_all($pdo) {
        return $pdo->query("SELECT id, nome, login, nivel, ativo FROM usuarios ORDER BY CASE nivel WHEN 'admin' THEN 1 WHEN 'master' THEN 2 ELSE 3 END, nome")->fetchAll(PDO::FETCH_OBJ);
    }

    public function update($id, $nome, $login, $nivel, $ativo, $pdo) {
        $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, login=?, nivel=?, ativo=? WHERE id=?");
        $stmt->execute([$nome, $login, $nivel, $ativo, $id]);
    }

    public function change_password($id, $senha, $pdo) {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash=? WHERE id=?");
        $stmt->execute([$hash, $id]);
    }

    public function delete($id, $pdo) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id=? AND nivel='agente'");
        $stmt->execute([$id]);
    }

    public function table_exists($pdo) {
        try {
            $pdo->query("SELECT 1 FROM usuarios LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
