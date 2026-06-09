#!/bin/sh

set -e

PB_URL="http://localhost:8090"
PB_ADMIN_EMAIL="admin@sidegigs.local"
PB_ADMIN_PASSWORD="admin123"

echo "Waiting for PocketBase to start..."
for i in $(seq 1 30); do
    if curl -sf "$PB_URL/api/health" > /dev/null 2>&1; then
        echo "PocketBase is ready."
        break
    fi
    sleep 1
done

if ! curl -sf "$PB_URL/api/health" > /dev/null 2>&1; then
    echo "PocketBase did not start in time."
    exit 1
fi

echo "Creating superuser via CLI..."
pocketbase superuser upsert "$PB_ADMIN_EMAIL" "$PB_ADMIN_PASSWORD" 2>&1

echo "Authenticating as superuser..."
RESP=$(curl -sf -X POST "$PB_URL/api/collections/_superusers/auth-with-password" \
    -H "Content-Type: application/json" \
    -d "{\"identity\":\"$PB_ADMIN_EMAIL\",\"password\":\"$PB_ADMIN_PASSWORD\"}")
TOKEN=$(echo "$RESP" | jq -r '.token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo "Failed to authenticate."
    exit 1
fi

echo "Authenticated successfully."

TIMESTAMP_FIELDS='{"name":"created","type":"autodate","onCreate":true},{"name":"updated","type":"autodate","onCreate":true,"onUpdate":true}'

create_collection() {
    local name="$1"
    local fields="$2"

    local existing=$(curl -sf "$PB_URL/api/collections" \
        -H "Authorization: Bearer $TOKEN" \
        | jq -r ".items[] | select(.name == \"$name\") | .id")
    if [ -n "$existing" ]; then
        echo "Collection '$name' already exists, skipping."
        return
    fi

    echo "Creating collection '$name'..."
    local all_fields=$(echo "$fields" | jq --argjson ts "[$TIMESTAMP_FIELDS]" '. + $ts')
    local result=$(curl -sf -X POST "$PB_URL/api/collections" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -d "{\"name\":\"$name\",\"type\":\"base\",\"fields\":$all_fields}" 2>&1)
    local id=$(echo "$result" | jq -r '.id // empty')
    if [ -n "$id" ]; then
        curl -sf -X PATCH "$PB_URL/api/collections/$id" \
            -H "Authorization: Bearer $TOKEN" \
            -H "Content-Type: application/json" \
            -d '{"listRule":"","viewRule":"","createRule":"","updateRule":"","deleteRule":""}' > /dev/null 2>&1
    fi
    echo "  $name: created"
}

echo ""
echo "Creating collections..."

create_collection "app_users" '[
    {"name":"userId","type":"text","required":true,"unique":true},
    {"name":"firstName","type":"text","required":true},
    {"name":"lastName","type":"text","required":true},
    {"name":"email","type":"email","required":true,"unique":true},
    {"name":"username","type":"text","required":true,"unique":true},
    {"name":"password","type":"text","required":true},
    {"name":"roles","type":"json"},
    {"name":"status","type":"text"}
]'

create_collection "clients" '[
    {"name":"clientId","type":"text","required":true,"unique":true},
    {"name":"nip","type":"text","required":true},
    {"name":"name","type":"text","required":true},
    {"name":"address","type":"text","required":true},
    {"name":"country","type":"text","required":true},
    {"name":"email","type":"email","required":true},
    {"name":"description","type":"text"},
    {"name":"contacts","type":"json"},
    {"name":"status","type":"text"}
]'

create_collection "workflows" '[
    {"name":"workflowId","type":"text","required":true,"unique":true},
    {"name":"name","type":"text","required":true},
    {"name":"stages","type":"json"},
    {"name":"transitions","type":"json"},
    {"name":"isDefault","type":"bool"}
]'

create_collection "tasks" '[
    {"name":"taskId","type":"text","required":true,"unique":true},
    {"name":"title","type":"text","required":true},
    {"name":"description","type":"text"},
    {"name":"creatorId","type":"text","required":true},
    {"name":"assigneeId","type":"text"},
    {"name":"clientId","type":"text"},
    {"name":"stageId","type":"text","required":true},
    {"name":"position","type":"number"},
    {"name":"parentTaskId","type":"text"},
    {"name":"status","type":"text"},
    {"name":"comments","type":"json"},
    {"name":"worklogs","type":"json"},
    {"name":"totalTimeSpent","type":"number"}
]'

create_collection "task_events" '[
    {"name":"eventId","type":"text","required":true,"unique":true},
    {"name":"taskId","type":"text","required":true},
    {"name":"userId","type":"text"},
    {"name":"type","type":"text","required":true},
    {"name":"data","type":"json"},
    {"name":"occurredAt","type":"text","required":true}
]'

echo ""
echo "All collections created."
