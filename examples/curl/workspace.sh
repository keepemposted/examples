#!/usr/bin/env bash
# The workspace the key belongs to: its time zone, its approval rule and its credits.
# Usage: KEEPEMPOSTED_API_KEY=kep_live_... ./workspace.sh
set -euo pipefail
BASE="${KEEPEMPOSTED_API_URL:-https://api.keepemposted.com}"
: "${KEEPEMPOSTED_API_KEY:?Set KEEPEMPOSTED_API_KEY to an API key (app.keepemposted.com, Settings, API keys)}"

curl -sS --fail-with-body "$BASE/v1/workspace" \
  -H "Authorization: Bearer $KEEPEMPOSTED_API_KEY"
echo
