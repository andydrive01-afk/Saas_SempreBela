<?php
header('Content-Type: application/json; charset=utf-8');

$cfg = dirname(__DIR__) . '/config.php';
if (!file_exists($cfg)) {
    echo json_encode(['ok' => false, 'msg' => 'config.php não encontrado. Salve as configurações do banco primeiro.']);
    exit;
}

include_once __DIR__ . '/connection.php';
try {
    $c    = new connection();
    $pdo  = $c->connect();
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Não foi possível conectar ao banco: ' . $e->getMessage()]);
    exit;
}

$errors = [];
$ok     = 0;

$statements = [

    "CREATE TABLE IF NOT EXISTS `clientes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nome` varchar(80) NOT NULL,
        `tel` varchar(80) NOT NULL DEFAULT '',
        `data_nasc` date NOT NULL DEFAULT '0000-00-00',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `servicos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `servico` varchar(80) NOT NULL,
        `valor` float NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `produtos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nome` varchar(50) NOT NULL,
        `quantidade` int(11) NOT NULL DEFAULT 0,
        `min` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `atendentes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nome` varchar(80) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `atendimentos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_cliente` int(11) NOT NULL,
        `nome_cliente` varchar(80) NOT NULL,
        `d_assiduidade` varchar(4) NOT NULL DEFAULT 'nao',
        `d_aniversario` varchar(4) NOT NULL DEFAULT 'nao',
        `promocao_percent` int(11) NOT NULL DEFAULT 0,
        `promocao_valor` int(11) NOT NULL DEFAULT 0,
        `metodo` varchar(50) NOT NULL,
        `valor_atendimento` float NOT NULL DEFAULT 0,
        `data_atendimento` date NOT NULL,
        `atendente_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `atendimento_ibfk_1` (`id_cliente`),
        KEY `fk_atendente` (`atendente_id`),
        CONSTRAINT `atendimentos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `atendimentos_atendente_fk` FOREIGN KEY (`atendente_id`) REFERENCES `atendentes` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `servicos_atendimento` (
        `id_servico` int(11) NOT NULL,
        `id_atendimento` int(11) NOT NULL,
        KEY `ids_atendimento_ibfk_1` (`id_atendimento`),
        KEY `id_servico` (`id_servico`),
        CONSTRAINT `servicos_atendimento_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE,
        CONSTRAINT `servicos_atendimento_ibfk_3` FOREIGN KEY (`id_servico`) REFERENCES `servicos` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `produtos_atendimento` (
        `id_produto` int(11) NOT NULL,
        `id_atendimento` int(11) NOT NULL,
        KEY `id_atendimento` (`id_atendimento`),
        KEY `produtos_atendimento_ibfk_2` (`id_produto`),
        CONSTRAINT `produtos_atendimento_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE,
        CONSTRAINT `produtos_atendimento_ibfk_2` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `financeiro` (
        `id` int(11) NOT NULL DEFAULT 1,
        `meta_semanal` float NOT NULL DEFAULT 0,
        `entrada_semanal` float NOT NULL DEFAULT 0,
        `saida_semanal` float NOT NULL DEFAULT 0,
        `ultimo_dia` date NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "INSERT IGNORE INTO `financeiro` (`id`,`meta_semanal`,`entrada_semanal`,`saida_semanal`,`ultimo_dia`)
     VALUES (1, 500, 0, 0, CURDATE())",

    "CREATE TABLE IF NOT EXISTS `despesas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `valor` float NOT NULL,
        `descricao` varchar(200) NOT NULL,
        `data_despesa` date NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `vendas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `origem` varchar(80) NOT NULL,
        `valor` float NOT NULL,
        `data_venda` date NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `venda_atendimento` (
        `id_venda` int(11) NOT NULL,
        `id_atendimento` int(11) NOT NULL,
        KEY `id_atendimento` (`id_atendimento`),
        KEY `id_venda` (`id_venda`),
        CONSTRAINT `venda_atendimento_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE,
        CONSTRAINT `venda_atendimento_ibfk_2` FOREIGN KEY (`id_venda`) REFERENCES `vendas` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `configuracoes` (
        `chave` varchar(80) NOT NULL,
        `valor` text,
        PRIMARY KEY (`chave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($statements as $sql) {
    try {
        $pdo->exec($sql);
        $ok++;
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}

if (empty($errors)) {
    echo json_encode(['ok' => true, 'msg' => "Banco instalado com sucesso! ({$ok} tabelas/registros criados)"]);
} else {
    echo json_encode([
        'ok'  => count($errors) < count($statements),
        'msg' => "Concluído com avisos: " . implode(' | ', $errors),
    ]);
}
exit;
