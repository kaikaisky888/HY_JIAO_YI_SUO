#!/bin/sh

# Railway uses $PORT env var, default to 80 for local Docker
export LISTEN_PORT=${PORT:-80}

# Generate nginx config from template (replace ${LISTEN_PORT})
envsubst '${LISTEN_PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

# Parse MYSQL_URL if provided by Railway (format: mysql://user:pass@host:port/dbname)
if [ -n "$MYSQL_URL" ]; then
    # Extract components from MYSQL_URL
    DB_USER=$(echo $MYSQL_URL | sed -E 's|mysql://([^:]+):.*|\1|')
    DB_PASS=$(echo $MYSQL_URL | sed -E 's|mysql://[^:]+:([^@]+)@.*|\1|')
    DB_HOST=$(echo $MYSQL_URL | sed -E 's|mysql://[^@]+@([^:]+):.*|\1|')
    DB_PORT=$(echo $MYSQL_URL | sed -E 's|mysql://[^@]+@[^:]+:([0-9]+)/.*|\1|')
    DB_NAME=$(echo $MYSQL_URL | sed -E 's|mysql://[^/]+/([^?]+).*|\1|')
    
    DATABASE_HOSTNAME=${DB_HOST}
    DATABASE_DATABASE=${DB_NAME}
    DATABASE_USERNAME=${DB_USER}
    DATABASE_PASSWORD=${DB_PASS}
    DATABASE_HOSTPORT=${DB_PORT}
fi

# Parse REDIS_URL if provided by Railway (format: redis://default:pass@host:port)
if [ -n "$REDIS_URL" ]; then
    REDIS_HOST=$(echo $REDIS_URL | sed -E 's|redis://[^@]+@([^:]+):.*|\1|')
fi

# Generate .env file for ThinkPHP from environment variables
cat > /var/www/html/.env <<EOF
APP_DEBUG = false

DATABASE_TYPE = ${DATABASE_TYPE:-mysql}
DATABASE_HOSTNAME = ${DATABASE_HOSTNAME:-127.0.0.1}
DATABASE_DATABASE = ${DATABASE_DATABASE:-curve_1}
DATABASE_USERNAME = ${DATABASE_USERNAME:-curve_1}
DATABASE_PASSWORD = ${DATABASE_PASSWORD}
DATABASE_HOSTPORT = ${DATABASE_HOSTPORT:-3306}
DATABASE_CHARSET = ${DATABASE_CHARSET:-utf8}
DATABASE_PREFIX = ${DATABASE_PREFIX:-fox_}
DATABASE_DRIVER = ${DATABASE_DRIVER:-mysql}

KLINE_DB_HOST = ${KLINE_DB_HOST:-127.0.0.1}
KLINE_DB_NAME = ${KLINE_DB_NAME:-curve_2}
KLINE_DB_USER = ${KLINE_DB_USER:-curve_2}
KLINE_DB_PASS = ${KLINE_DB_PASS}

REDIS_HOST = ${REDIS_HOST:-127.0.0.1}
EOF

chown www-data:www-data /var/www/html/.env

echo "[entrypoint] Listening on port $LISTEN_PORT"
echo "[entrypoint] DB host: $DATABASE_HOSTNAME"

exec "$@"
