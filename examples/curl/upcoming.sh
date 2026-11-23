#!/usr/bin/env bash
# The posts that wait for a person's approval, then the ones that are scheduled (the first page of each).
# Usage: KEEPEMPOSTED_API_KEY=kep_live_... ./upcoming.sh
set -euo pipefail
BASE="${KEEPEMPOSTED_API_URL:-https://api.keepemposted.com}"
: "${KEEPEMPOSTED_API_KEY:?Set KEEPEMPOSTED_API_KEY to an API key (app.keepemposted.com, Settings, API keys)}"

for STATUS in pending_approval scheduled; do
  curl -sS --fail-with-body "$BASE/v1/posts?status=$STATUS&limit=50" \
    -H "Authorization: Bearer $KEEPEMPOSTED_API_KEY"
  echo
done
