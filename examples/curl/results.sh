#!/usr/bin/env bash
# How the posts that went out in the last 7, 30 or 90 days are doing, per post, account and platform.
# Usage: KEEPEMPOSTED_API_KEY=kep_live_... ./results.sh [7|30|90]
set -euo pipefail
BASE="${KEEPEMPOSTED_API_URL:-https://api.keepemposted.com}"
: "${KEEPEMPOSTED_API_KEY:?Set KEEPEMPOSTED_API_KEY to an API key (app.keepemposted.com, Settings, API keys)}"
DAYS="${1:-30}"

curl -sS --fail-with-body "$BASE/v1/results?days=$DAYS" \
  -H "Authorization: Bearer $KEEPEMPOSTED_API_KEY"
echo
