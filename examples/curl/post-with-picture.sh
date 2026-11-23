#!/usr/bin/env bash
# Fetch a picture from a web address, describe it, and post it to one or more accounts.
# Usage: KEEPEMPOSTED_API_KEY=kep_live_... ./post-with-picture.sh WHEN PICTURE_URL "ALT TEXT" "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]
# WHEN is draft, next_free or a time such as 2026-11-27T09:00. Needs jq.
set -euo pipefail
BASE="${KEEPEMPOSTED_API_URL:-https://api.keepemposted.com}"
: "${KEEPEMPOSTED_API_KEY:?Set KEEPEMPOSTED_API_KEY to an API key (app.keepemposted.com, Settings, API keys)}"
WHEN="${1:?Give WHEN: draft, next_free or a time such as 2026-11-27T09:00}"
PICTURE_URL="${2:?Give the public https address of the picture}"
ALT_TEXT="${3:?Give the alt text: what the picture shows}"
TEXT="${4:?Give the text of the post}"
shift 4
[ "$#" -gt 0 ] || { echo "Give one or more account ids (accounts.sh lists them)" >&2; exit 1; }

# 1. Keep 'em Posted fetches the picture and stores it; the answer has its id.
PICTURE=$(jq -n --arg url "$PICTURE_URL" --arg alt "$ALT_TEXT" '{url: $url, alt_text: $alt}')
MEDIA=$(curl -sS --fail-with-body -X POST "$BASE/v1/media/from-url" \
  -H "Authorization: Bearer $KEEPEMPOSTED_API_KEY" \
  -H "Content-Type: application/json" \
  -d "$PICTURE") || { echo "$MEDIA" >&2; exit 1; }
MEDIA_ID=$(jq -r '.media.id' <<<"$MEDIA")

# 2. The post with that picture.
IDEMPOTENCY_KEY="${IDEMPOTENCY_KEY:-$(uuidgen 2>/dev/null || cat /proc/sys/kernel/random/uuid)}"
BODY=$(jq -n --arg text "$TEXT" --arg when "$WHEN" --arg media "$MEDIA_ID" '
  {text: $text, media_ids: [$media], accounts: ($ARGS.positional | map({account_id: .}))}
  + (if $when == "draft" then {} else {schedule_at: $when} end)' --args "$@")

curl -sS --fail-with-body -X POST "$BASE/v1/posts" \
  -H "Authorization: Bearer $KEEPEMPOSTED_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $IDEMPOTENCY_KEY" \
  -d "$BODY"
echo
