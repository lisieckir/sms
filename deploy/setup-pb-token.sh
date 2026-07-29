#!/bin/sh
# One-time setup: generate a PocketBase admin token and save it on the server.
# After this, deploy.sh uses the token instead of plaintext password.
# Usage: ./deploy/setup-pb-token.sh [inventory]

DIR="$(cd "$(dirname "$0")" && pwd)"
INVENTORY="${1:-$DIR/inventory}"

if [ -f "$INVENTORY" ]; then
    . "$INVENTORY"
fi

REMOTE_HOST="${REMOTE_HOST:?inventory: REMOTE_HOST not set}"
REMOTE_USER="${REMOTE_USER:-root}"
SSH_KEY="${SSH_KEY:-}"
SSH_PORT="${SSH_PORT:-22}"
SSH_OPTS="${SSH_KEY:+-i $SSH_KEY} -p $SSH_PORT -o StrictHostKeyChecking=accept-new"
remote() { ssh $SSH_OPTS "$REMOTE_USER@$REMOTE_HOST" "$@"; }

PB_URL="${POCKETBASE_URL:-http://127.0.0.1:8090}"
PB_EMAIL="${POCKETBASE_ADMIN_EMAIL:?Set POCKETBASE_ADMIN_EMAIL in inventory}"
PB_PASSWORD="${POCKETBASE_ADMIN_PASSWORD:?Set POCKETBASE_ADMIN_PASSWORD in inventory}"

echo "==> Authenticating to PocketBase on $REMOTE_HOST..."

TOKEN=$(remote "curl -s -X POST '$PB_URL/api/collections/_superusers/auth-with-password' \
    -H 'Content-Type: application/json' \
    -d '{\"identity\":\"$PB_EMAIL\",\"password\":\"$PB_PASSWORD\"}' | jq -r '.token'")

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo "ERR  Authentication failed. Check POCKETBASE_ADMIN_EMAIL and POCKETBASE_ADMIN_PASSWORD."
    exit 1
fi

echo "==> Saving token to /etc/sms/.pocketbase_token"
remote "mkdir -p /etc/sms && echo '$TOKEN' > /etc/sms/.pocketbase_token && chmod 600 /etc/sms/.pocketbase_token"

echo " OK  Token saved. You can now remove POCKETBASE_ADMIN_PASSWORD from inventory."
