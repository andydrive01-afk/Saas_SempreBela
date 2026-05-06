#!/bin/bash

MYSQL_BIN="/nix/store/s2lbn1axpc79kwnc829k5idkwabfq459-mysql-8.0.42/bin"
MYSQL_DATA="/home/runner/mysql8-data"

rm -f /tmp/mysql.sock /tmp/mysql.sock.lock /tmp/mysql.pid

if [ ! -d "$MYSQL_DATA/mysql" ]; then
    echo "Initializing MySQL data directory..."
    mkdir -p "$MYSQL_DATA"
    $MYSQL_BIN/mysqld --initialize-insecure --user=runner --datadir=$MYSQL_DATA 2>&1
    echo "MySQL initialization complete."
fi

$MYSQL_BIN/mysqld \
  --user=runner \
  --datadir=$MYSQL_DATA \
  --socket=/tmp/mysql.sock \
  --pid-file=/home/runner/mysql.pid \
  --port=3306 \
  --mysqlx=OFF \
  --bind-address=127.0.0.1 &

echo "Waiting for MySQL to start..."
for i in $(seq 1 30); do
  sleep 1
  if $MYSQL_BIN/mysqladmin --socket=/tmp/mysql.sock -u root ping 2>/dev/null | grep -q "alive"; then
    echo "MySQL is ready!"
    break
  fi
  echo "Waiting... ($i)"
done

$MYSQL_BIN/mysql --socket=/tmp/mysql.sock -u root -e "CREATE DATABASE IF NOT EXISTS \`database\`;" 2>/dev/null

if ! $MYSQL_BIN/mysql --socket=/tmp/mysql.sock -u root database -e "SHOW TABLES;" 2>/dev/null | grep -q "atendimentos"; then
    echo "Importing database schema..."
    $MYSQL_BIN/mysql --socket=/tmp/mysql.sock -u root database < /home/runner/workspace/sql/database.sql
    echo "Schema imported."
fi

echo "Applying schema migrations..."
$MYSQL_BIN/mysql --socket=/tmp/mysql.sock -u root database -e "
    CREATE TABLE IF NOT EXISTS atendentes (
        id INT NOT NULL AUTO_INCREMENT,
        nome VARCHAR(80) NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
" 2>/dev/null

if ! $MYSQL_BIN/mysql --socket=/tmp/mysql.sock -u root database -e "SHOW COLUMNS FROM atendimentos LIKE 'atendente_id';" 2>/dev/null | grep -q "atendente_id"; then
    $MYSQL_BIN/mysql --socket=/tmp/mysql.sock -u root database -e "
        ALTER TABLE atendimentos ADD COLUMN atendente_id INT NULL;
        ALTER TABLE atendimentos ADD CONSTRAINT atendimentos_atendente_fk
            FOREIGN KEY (atendente_id) REFERENCES atendentes(id) ON DELETE SET NULL;
    " 2>/dev/null
    echo "Column atendente_id added."
fi
echo "Migrations complete."


# Migrate settings table
$MYSQL_BIN/mysql --socket=/tmp/mysql.sock -u root database -e "CREATE TABLE IF NOT EXISTS configuracoes (chave VARCHAR(80) NOT NULL PRIMARY KEY, valor TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" 2>/dev/null
echo "Settings table ready."

# Cria config.php para o ambiente Replit se não existir
CONFIG_FILE="/home/runner/workspace/config.php"
if [ ! -f "$CONFIG_FILE" ]; then
    cat > "$CONFIG_FILE" << 'EOPHP'
<?php
// Gerado automaticamente pelo start.sh (ambiente Replit)
// Em hospedagem compartilhada, configure pelo setup.php
define('DB_HOST',    '127.0.0.1');
define('DB_NAME',    'database');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_PORT',    3306);
define('DB_CHARSET', 'utf8mb4');
EOPHP
    echo "config.php criado para ambiente Replit."
fi

echo "Starting PHP server on port 5000..."
exec php -S 0.0.0.0:5000 -t /home/runner/workspace
