#!/usr/bin/env bash
# Write one post for one or more accounts: as drafts, for each account's next free posting time, or for a time you give.
# Usage: KEEPEMPOSTED_API_KEY=kep_live_... ./create-post.sh WHEN "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]
# WHEN is draft, next_free or a time such as 2026-11-27T09:00 (the workspace's time zone). Needs jq to build the body.
set -euo pipefail
BASE="${KEEPEMPOSTED_API_URL:-https://api.keepemposted.com}"
: "${KEEPEMPOSTED_API_KEY:?Set KEEPEMPOSTED_API_KEY to an API key (app.keepemposted.com, Settings, API keys)}"
WHEN="${1:?Give WHEN: draft, next_free or a time such as 2026-11-27T09:00}"
TEXT="${2:?Give the text of the post}"
shift 2
[ "$#" -gt 0 ] || { echo "Give one or more account ids (accounts.sh lists them)" >&2; exit 1; }

# The same Idempotency-Key on a repeated request gives back the post already made, never a second one.
IDEMPOTENCY_KEY="${IDEMPOTENCY_KEY:-$(uuidgen 2>/dev/null || cat /proc/sys/kernel/random/uuid)}"

BODY=$(jq -n --arg text "$TEXT" --arg when "$WHEN" '
  {text: $text, accounts: ($ARGS.positional | map({account_id: .}))}
  + (if $when == "draft" then {} else {schedule_at: $when} end)' --args "$@")

curl -sS --fail-with-body -X POST "$BASE/v1/posts" \
  -H "Authorization: Bearer $KEEPEMPOSTED_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $IDEMPOTENCY_KEY" \
  -d "$BODY"
echo
