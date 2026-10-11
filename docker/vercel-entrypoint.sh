#!/bin/sh
set -eu

# Vercel supplies environment variables at runtime, after the image is built.
if [ -n "${MYSQL_SSL_CA_BASE64:-}" ]; then
    umask 077
    printf '%s' "$MYSQL_SSL_CA_BASE64" | base64 -d > /tmp/utopia-mysql-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/utopia-mysql-ca.pem
fi

exec frankenphp run --config /etc/frankenphp/Caddyfile
