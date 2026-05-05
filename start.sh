#!/bin/bash

MYSQL_BIN="/nix/store/s2lbn1axpc79kwnc829k5idkwabfq459-mysql-8.0.42/bin"
MYSQL_DATA="/home/runner/mysql8-data"

rm -f /tmp/mysql.sock /tmp/mysql.sock.lock /tmp/mysql.pid

$MYSQL_BIN/mysqld \
  --user=runner \
  --datadir=$MYSQL_DATA \
  --socket=/tmp/mysql.sock \
  --pid-file=/home/runner/mysql.pid \
  --port=3306 \
  --mysqlx=OFF \
  --bind-address=127.0.0.1 &

MYSQL_PID=$!

echo "Waiting for MySQL to start..."
for i in $(seq 1 30); do
  sleep 1
  if $MYSQL_BIN/mysqladmin --socket=/tmp/mysql.sock -u root ping 2>/dev/null | grep -q "alive"; then
    echo "MySQL is ready!"
    break
  fi
  echo "Waiting... ($i)"
done

echo "Starting PHP server on port 5000..."
exec php -S 0.0.0.0:5000 -t /home/runner/workspace
