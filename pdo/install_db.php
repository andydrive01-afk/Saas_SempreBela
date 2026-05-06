<?php
header('Content-Type: application/json; charset=utf-8');

// Determine driver — POST params take precedence (wizard), otherwise config.php
$driver_post = strtolower(trim($_POST['db_driver'] ?? ''));
if ($driver_post) {
    $driver = $driver_post;
    $host   = trim($_POST['db_host'] ?? 'localhost');
    $name   = trim($_POST['db_name'] ?? '');
    $user   = trim($_POST['db_user'] ?? '');
    $pass   = trim($_POST['db_pass'] ?? '');
    $port   = (int)($_POST['db_port'] ?? 3306);
    $file   = trim($_POST['db_file'] ?? '');
} else {
    $cfg = dirname(__DIR__) . '/config.php';
    if (!file_exists($cfg)) {
        echo json_encode(['ok' => false, 'msg' => 'config.php não encontrado.']);
        exit;
    }
    include_once $cfg;
    $driver = strtolower(defined('DB_DRIVER') ? DB_DRIVER : 'mysql');
    $host   = defined('DB_HOST') ? DB_HOST : 'localhost';
    $name   = defined('DB_NAME') ? DB_NAME : '';
    $user   = defined('DB_USER') ? DB_USER : '';
    $pass   = defined('DB_PASS') ? DB_PASS : '';
    $port   = defined('DB_PORT') ? DB_PORT : 3306;
    $file   = defined('DB_FILE') ? DB_FILE : '';
}

// Connect
try {
    if ($driver === 'sqlite') {
        if (!$file) $file = dirname(__DIR__) . '/data/salao.sqlite';
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $pdo = new PDO("sqlite:{$file}", null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA foreign_keys = ON;');
    } elseif ($driver === 'pgsql') {
        $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$name}", $user, $pass,
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } else {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                       $user, $pass,
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Erro de conexão: ' . $e->getMessage()]);
    exit;
}

// ── SQL helpers per driver ──────────────────────────────────────────────────
function pk($driver) {
    if ($driver === 'sqlite') return 'INTEGER PRIMARY KEY AUTOINCREMENT';
    if ($driver === 'pgsql')  return 'SERIAL PRIMARY KEY';
    return 'INT NOT NULL AUTO_INCREMENT';
}
function intcol($driver)  { return $driver === 'sqlite' ? 'INTEGER' : 'INT(11)'; }
function floatcol($driver){ return $driver === 'sqlite' ? 'REAL'    : 'FLOAT'; }
function strcol($n,$driver){ return $driver === 'sqlite' ? 'TEXT'   : "VARCHAR({$n})"; }
function engine($driver)  { return ($driver === 'mysql' || $driver === 'mariadb') ? 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : ''; }
function pk_tail($driver) { return ($driver !== 'sqlite' && $driver !== 'pgsql') ? ', PRIMARY KEY (`id`)' : ''; }
function now_fn($driver)  { return $driver === 'pgsql' ? 'NOW()' : ($driver === 'sqlite' ? "date('now')" : 'CURDATE()'); }
function insert_ignore($driver, $sql) {
    if ($driver === 'sqlite') return str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $sql);
    if ($driver === 'pgsql')  return $sql . ' ON CONFLICT (id) DO NOTHING';
    return $sql;
}
function qi($driver, $name) {
    // quote identifier
    if ($driver === 'pgsql') return '"' . $name . '"';
    return '`' . $name . '`';
}

// ── Build statements ────────────────────────────────────────────────────────
$d = $driver;
$q = fn($n) => qi($d, $n);
$s = fn($n) => strcol($n, $d);
$i = fn()   => intcol($d);
$f = fn()   => floatcol($d);
$e = fn()   => engine($d);
$pt = fn()  => pk_tail($d);
$p  = fn()  => pk($d);

$statements = [];

// usuarios
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        login TEXT NOT NULL UNIQUE,
        senha_hash TEXT NOT NULL,
        nivel TEXT NOT NULL DEFAULT 'agente',
        ativo INTEGER NOT NULL DEFAULT 1,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS usuarios (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(80) NOT NULL,
        login VARCHAR(80) NOT NULL UNIQUE,
        senha_hash VARCHAR(255) NOT NULL,
        nivel VARCHAR(20) NOT NULL DEFAULT 'agente',
        ativo SMALLINT NOT NULL DEFAULT 1,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `usuarios` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `nome` VARCHAR(80) NOT NULL,
        `login` VARCHAR(80) NOT NULL UNIQUE,
        `senha_hash` VARCHAR(255) NOT NULL,
        `nivel` ENUM('admin','master','agente') NOT NULL DEFAULT 'agente',
        `ativo` TINYINT(1) NOT NULL DEFAULT 1,
        `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// clientes
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS clientes (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, tel TEXT NOT NULL DEFAULT '', data_nasc DATE NOT NULL DEFAULT '0000-00-00')";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS clientes (id SERIAL PRIMARY KEY, nome VARCHAR(80) NOT NULL, tel VARCHAR(80) NOT NULL DEFAULT '', data_nasc DATE)";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `clientes` (`id` int(11) NOT NULL AUTO_INCREMENT, `nome` varchar(80) NOT NULL, `tel` varchar(80) NOT NULL DEFAULT '', `data_nasc` date NOT NULL DEFAULT '0000-00-00', PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// servicos
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS servicos (id INTEGER PRIMARY KEY AUTOINCREMENT, servico TEXT NOT NULL, valor REAL NOT NULL)";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS servicos (id SERIAL PRIMARY KEY, servico VARCHAR(80) NOT NULL, valor FLOAT NOT NULL)";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `servicos` (`id` int(11) NOT NULL AUTO_INCREMENT, `servico` varchar(80) NOT NULL, `valor` float NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// produtos
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS produtos (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, quantidade INTEGER NOT NULL DEFAULT 0, min INTEGER NOT NULL DEFAULT 0)";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS produtos (id SERIAL PRIMARY KEY, nome VARCHAR(50) NOT NULL, quantidade INT NOT NULL DEFAULT 0, min INT NOT NULL DEFAULT 0)";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `produtos` (`id` int(11) NOT NULL AUTO_INCREMENT, `nome` varchar(50) NOT NULL, `quantidade` int(11) NOT NULL DEFAULT 0, `min` int(11) NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// atendentes
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS atendentes (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL)";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS atendentes (id SERIAL PRIMARY KEY, nome VARCHAR(80) NOT NULL)";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `atendentes` (`id` int(11) NOT NULL AUTO_INCREMENT, `nome` varchar(80) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// atendimentos
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS atendimentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_cliente INTEGER NOT NULL,
        nome_cliente TEXT NOT NULL,
        d_assiduidade TEXT NOT NULL DEFAULT 'nao',
        d_aniversario TEXT NOT NULL DEFAULT 'nao',
        promocao_percent INTEGER NOT NULL DEFAULT 0,
        promocao_valor INTEGER NOT NULL DEFAULT 0,
        metodo TEXT NOT NULL,
        valor_atendimento REAL NOT NULL DEFAULT 0,
        data_atendimento DATE NOT NULL,
        atendente_id INTEGER DEFAULT NULL,
        FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE,
        FOREIGN KEY (atendente_id) REFERENCES atendentes(id) ON DELETE SET NULL
    )";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS atendimentos (
        id SERIAL PRIMARY KEY,
        id_cliente INT NOT NULL,
        nome_cliente VARCHAR(80) NOT NULL,
        d_assiduidade VARCHAR(4) NOT NULL DEFAULT 'nao',
        d_aniversario VARCHAR(4) NOT NULL DEFAULT 'nao',
        promocao_percent INT NOT NULL DEFAULT 0,
        promocao_valor INT NOT NULL DEFAULT 0,
        metodo VARCHAR(50) NOT NULL,
        valor_atendimento FLOAT NOT NULL DEFAULT 0,
        data_atendimento DATE NOT NULL,
        atendente_id INT DEFAULT NULL,
        FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE,
        FOREIGN KEY (atendente_id) REFERENCES atendentes(id) ON DELETE SET NULL
    )";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `atendimentos` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// servicos_atendimento
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS servicos_atendimento (id_servico INTEGER NOT NULL, id_atendimento INTEGER NOT NULL, FOREIGN KEY (id_atendimento) REFERENCES atendimentos(id) ON DELETE CASCADE, FOREIGN KEY (id_servico) REFERENCES servicos(id) ON DELETE CASCADE)";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS servicos_atendimento (id_servico INT NOT NULL, id_atendimento INT NOT NULL, FOREIGN KEY (id_atendimento) REFERENCES atendimentos(id) ON DELETE CASCADE, FOREIGN KEY (id_servico) REFERENCES servicos(id) ON DELETE CASCADE)";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `servicos_atendimento` (`id_servico` int(11) NOT NULL, `id_atendimento` int(11) NOT NULL, KEY `ids_atendimento_ibfk_1` (`id_atendimento`), KEY `id_servico` (`id_servico`), CONSTRAINT `servicos_atendimento_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE, CONSTRAINT `servicos_atendimento_ibfk_3` FOREIGN KEY (`id_servico`) REFERENCES `servicos` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// produtos_atendimento
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS produtos_atendimento (id_produto INTEGER NOT NULL, id_atendimento INTEGER NOT NULL, FOREIGN KEY (id_atendimento) REFERENCES atendimentos(id) ON DELETE CASCADE, FOREIGN KEY (id_produto) REFERENCES produtos(id) ON DELETE CASCADE)";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS produtos_atendimento (id_produto INT NOT NULL, id_atendimento INT NOT NULL, FOREIGN KEY (id_atendimento) REFERENCES atendimentos(id) ON DELETE CASCADE, FOREIGN KEY (id_produto) REFERENCES produtos(id) ON DELETE CASCADE)";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `produtos_atendimento` (`id_produto` int(11) NOT NULL, `id_atendimento` int(11) NOT NULL, KEY `id_atendimento` (`id_atendimento`), KEY `produtos_atendimento_ibfk_2` (`id_produto`), CONSTRAINT `produtos_atendimento_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE, CONSTRAINT `produtos_atendimento_ibfk_2` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// financeiro
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS financeiro (id INTEGER NOT NULL DEFAULT 1, meta_semanal REAL NOT NULL DEFAULT 0, entrada_semanal REAL NOT NULL DEFAULT 0, saida_semanal REAL NOT NULL DEFAULT 0, ultimo_dia DATE NOT NULL, PRIMARY KEY (id))";
    $statements[] = "INSERT OR IGNORE INTO financeiro (id,meta_semanal,entrada_semanal,saida_semanal,ultimo_dia) VALUES (1,500,0,0,date('now'))";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS financeiro (id INT NOT NULL DEFAULT 1, meta_semanal FLOAT NOT NULL DEFAULT 0, entrada_semanal FLOAT NOT NULL DEFAULT 0, saida_semanal FLOAT NOT NULL DEFAULT 0, ultimo_dia DATE NOT NULL, PRIMARY KEY (id))";
    $statements[] = "INSERT INTO financeiro (id,meta_semanal,entrada_semanal,saida_semanal,ultimo_dia) VALUES (1,500,0,0,NOW()::DATE) ON CONFLICT (id) DO NOTHING";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `financeiro` (`id` int(11) NOT NULL DEFAULT 1, `meta_semanal` float NOT NULL DEFAULT 0, `entrada_semanal` float NOT NULL DEFAULT 0, `saida_semanal` float NOT NULL DEFAULT 0, `ultimo_dia` date NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $statements[] = "INSERT IGNORE INTO `financeiro` (`id`,`meta_semanal`,`entrada_semanal`,`saida_semanal`,`ultimo_dia`) VALUES (1, 500, 0, 0, CURDATE())";
}

// despesas
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS despesas (id INTEGER PRIMARY KEY AUTOINCREMENT, valor REAL NOT NULL, descricao TEXT NOT NULL, data_despesa DATE NOT NULL)";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS despesas (id SERIAL PRIMARY KEY, valor FLOAT NOT NULL, descricao VARCHAR(200) NOT NULL, data_despesa DATE NOT NULL)";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `despesas` (`id` int(11) NOT NULL AUTO_INCREMENT, `valor` float NOT NULL, `descricao` varchar(200) NOT NULL, `data_despesa` date NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// vendas
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS vendas (id INTEGER PRIMARY KEY AUTOINCREMENT, origem TEXT NOT NULL, valor REAL NOT NULL, data_venda DATE NOT NULL)";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS vendas (id SERIAL PRIMARY KEY, origem VARCHAR(80) NOT NULL, valor FLOAT NOT NULL, data_venda DATE NOT NULL)";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `vendas` (`id` int(11) NOT NULL AUTO_INCREMENT, `origem` varchar(80) NOT NULL, `valor` float NOT NULL, `data_venda` date NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// venda_atendimento
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS venda_atendimento (id_venda INTEGER NOT NULL, id_atendimento INTEGER NOT NULL, FOREIGN KEY (id_atendimento) REFERENCES atendimentos(id) ON DELETE CASCADE, FOREIGN KEY (id_venda) REFERENCES vendas(id) ON DELETE CASCADE)";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS venda_atendimento (id_venda INT NOT NULL, id_atendimento INT NOT NULL, FOREIGN KEY (id_atendimento) REFERENCES atendimentos(id) ON DELETE CASCADE, FOREIGN KEY (id_venda) REFERENCES vendas(id) ON DELETE CASCADE)";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `venda_atendimento` (`id_venda` int(11) NOT NULL, `id_atendimento` int(11) NOT NULL, KEY `id_atendimento` (`id_atendimento`), KEY `id_venda` (`id_venda`), CONSTRAINT `venda_atendimento_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE, CONSTRAINT `venda_atendimento_ibfk_2` FOREIGN KEY (`id_venda`) REFERENCES `vendas` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// configuracoes
if ($d === 'sqlite') {
    $statements[] = "CREATE TABLE IF NOT EXISTS configuracoes (chave TEXT NOT NULL, valor TEXT, PRIMARY KEY (chave))";
} elseif ($d === 'pgsql') {
    $statements[] = "CREATE TABLE IF NOT EXISTS configuracoes (chave VARCHAR(80) NOT NULL, valor TEXT, PRIMARY KEY (chave))";
} else {
    $statements[] = "CREATE TABLE IF NOT EXISTS `configuracoes` (`chave` varchar(80) NOT NULL, `valor` text, PRIMARY KEY (`chave`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

// ── Execute ─────────────────────────────────────────────────────────────────
$errors = [];
$ok     = 0;
foreach ($statements as $sql) {
    try {
        $pdo->exec($sql);
        $ok++;
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}

if (empty($errors)) {
    echo json_encode(['ok' => true, 'msg' => "✅ Banco instalado com sucesso! ({$ok} tabelas/registros criados)"]);
} else {
    echo json_encode([
        'ok'  => count($errors) < count($statements),
        'msg' => "Concluído com avisos: " . implode(' | ', $errors),
    ]);
}
exit;
