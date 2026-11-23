#!/usr/bin/env bash
# The connected accounts with their ids, the kinds of post each one takes and what each costs, and the brands.
# Usage: KEEPEMPOSTED_API_KEY=kep_live_... ./accounts.sh
set -euo pipefail
BASE="${KEEPEMPOSTED_API_URL:-https://api.keepemposted.com}"
: "${KEEPEMPOSTED_API_KEY:?Set KEEPEMPOSTED_API_KEY to an API key (app.keepemposted.com, Settings, API keys)}"

curl -sS --fail-with-body "$BASE/v1/accounts" \
  -H "Authorization: Bearer $KEEPEMPOSTED_API_KEY"
echo
