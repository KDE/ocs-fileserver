#!/usr/bin/env bash
#
# End-to-end test for the reworked download verification in Files::getDownload().
#
# Drives the running Docker fileserver over HTTP with tokens minted by
# scripts/mintTestToken (same secrets/JWT as the app), and inspects the
# server-side Redis anti-replay state. See scripts/mintTestToken for modes.
#
# Prerequisites:
#   docker compose up ocs_files_app ocs_files_redis   # app on :8086, redis enabled
#   A known, ACTIVE file fixture (exists in DB; on disk too for the 200 cases).
#
# Required env (fixture):
#   CLIENT   client_id (must exist in clients.ini)
#   CID      collection_id of the file
#   FID      file id
#   FNAME    file name (last URL path segment)
#
# Optional env (defaults suit the recommended test [download] config):
#   BASE_URL   (http://localhost:8086/api)
#   DC         docker compose command ("docker compose")
#   APP_SVC    (ocs_files_app)   REDIS_SVC (ocs_files_redis)
#   REDIS_DB   (14)              NS (ocs-files-2)   -> RedisCache namespace
#   MAX_SERVES (3)               IP_LIMIT (5)       -> must match [download] config
#   RUN_IP_NET (1)  RUN_REDIS_DOWN (0)              -> toggle heavier/destructive cases
#
# Usage:
#   CLIENT=1387085484 CID=12345 FID=67890 FNAME=foo.zip ./scripts/testDownloadFlow.sh

set -u

BASE_URL="${BASE_URL:-http://localhost:8086/api}"
DC="${DC:-docker compose}"
APP_SVC="${APP_SVC:-ocs_files_app}"
REDIS_SVC="${REDIS_SVC:-ocs_files_redis}"
REDIS_DB="${REDIS_DB:-14}"
NS="${NS:-ocs-files-2}"
MAX_SERVES="${MAX_SERVES:-3}"
IP_LIMIT="${IP_LIMIT:-5}"
RUN_IP_NET="${RUN_IP_NET:-1}"
RUN_REDIS_DOWN="${RUN_REDIS_DOWN:-0}"

: "${CLIENT:?set CLIENT}" "${CID:?set CID}" "${FID:?set FID}" "${FNAME:?set FNAME}"

PREFIX=""
[ -n "$NS" ] && PREFIX="${NS}:_"

HDR="$(mktemp)"
trap 'rm -f "$HDR"' EXIT

PASS=0; FAIL=0
ok()  { PASS=$((PASS+1)); printf '  \033[32mPASS\033[0m %s\n' "$1"; }
ng()  { FAIL=$((FAIL+1)); printf '  \033[31mFAIL\033[0m %s\n' "$1"; }
head_case() { printf '\n\033[1m# %s\033[0m\n' "$1"; }

check()    { [ "$2" = "$3" ] && ok "$1 (=$3)" || ng "$1 (expected $2, got $3)"; }
check_ne() { [ "$2" != "$3" ] && ok "$1 (!=$2, got $3)" || ng "$1 (unexpectedly =$3)"; }

mint()  { $DC exec -T "$APP_SVC" php scripts/mintTestToken "$CLIENT" "$CID" "$FID" "$1" | tr -d '\r'; }
req()   { # $1=jwt ; sets STATUS, headers in $HDR
  local url="${BASE_URL}/files/download/j/${1}/${FNAME}?format=json"
  STATUS=$(curl -s -o /dev/null -D "$HDR" -w '%{http_code}' "$url")
}
hdr()   { grep -i "^$1:" "$HDR" | tr -d '\r' | awk -F': ' '{print $2}' | head -1; }
rget()  { $DC exec -T "$REDIS_SVC" redis-cli -n "$REDIS_DB" get "${PREFIX}$1" | tr -d '\r\n'; }
rset()  { $DC exec -T "$REDIS_SVC" redis-cli -n "$REDIS_DB" set "${PREFIX}$1" "$2" >/dev/null; }
rdel()  { $DC exec -T "$REDIS_SVC" sh -c "redis-cli -n $REDIS_DB --scan --pattern '${PREFIX}$1' | xargs -r -n 50 redis-cli -n $REDIS_DB del" >/dev/null 2>&1 || true; }

# Clean slate for this test's keyspace.
rdel 'dl:*'

# ---------------------------------------------------------------------------
head_case "1-3  Token lifecycle: first serve, resume, serve limit"
read -r JWT JTI < <(mint valid)
req "$JWT"; check "1a first click -> 200" 200 "$STATUS"
check "1b dl:cnt == 1" "i:1;" "$(rget "dl:cnt:${JTI}")"
[ -n "$(rget "dl:act:${JTI}")" ] && ok "1c dl:act set" || ng "1c dl:act missing"
req "$JWT"; check "2a second click (resume) -> 200" 200 "$STATUS"
check "2b dl:cnt == 2 (not a new unique)" "i:2;" "$(rget "dl:cnt:${JTI}")"
req "$JWT"; check "3a third click -> 200" 200 "$STATUS"
req "$JWT"; check "3b over limit -> 429" 429 "$STATUS"
check "3c X-RateLimit-Limit == $MAX_SERVES" "$MAX_SERVES" "$(hdr X-RateLimit-Limit)"
[ -n "$(hdr Retry-After)" ] && ok "3d Retry-After present" || ng "3d Retry-After missing"

# ---------------------------------------------------------------------------
head_case "4  Activation window elapsed (pre-aged first-seen)"
read -r JWT JTI < <(mint valid)
rset "dl:act:${JTI}" 'i:1600000000;'   # first-seen far in the past (serialized int)
req "$JWT"; check "consumed -> 410" 410 "$STATUS"

# ---------------------------------------------------------------------------
head_case "5  Token expired (exp/t in the past)"
read -r JWT JTI < <(mint expired)
req "$JWT"; check "expired -> 410" 410 "$STATUS"

# ---------------------------------------------------------------------------
head_case "6  Eternal-link sanity cap (exp - iat > max_link_age)"
read -r JWT JTI < <(mint eternal)
req "$JWT"; check "too long -> 400" 400 "$STATUS"

# ---------------------------------------------------------------------------
head_case "7  Tampered signature"
read -r JWT JTI < <(mint valid)
TAMPERED="${JWT%?}$([ "${JWT: -1}" = "A" ] && echo B || echo A)"
req "$TAMPERED"; check_ne "bad signature -> not 200" 200 "$STATUS"

# ---------------------------------------------------------------------------
head_case "8  Wrong download hash 's'"
read -r JWT JTI < <(mint badhash)
req "$JWT"; check "bad hash -> 400" 400 "$STATUS"

# ---------------------------------------------------------------------------
head_case "9  Soft IP binding (mismatch must NOT block)"
read -r JWT JTI < <(mint ipbind)
req "$JWT"; check "ip mismatch -> still 200" 200 "$STATUS"
$DC logs --since 30s "$APP_SVC" 2>&1 | grep -q "IP mismatch (soft)" \
  && ok "9b soft-mismatch logged" || ng "9b soft-mismatch log line not found"

# ---------------------------------------------------------------------------
head_case "11 Legacy token (only s + t, no jti/iat/exp)"
read -r JWT JTI < <(mint legacy)
req "$JWT"; check "legacy fallback -> 200" 200 "$STATUS"

# ---------------------------------------------------------------------------
head_case "12 Anonymous download (empty user id)"
read -r JWT JTI < <(mint anon)
req "$JWT"; check "anonymous -> 200" 200 "$STATUS"

# ---------------------------------------------------------------------------
if [ "$RUN_IP_NET" = "1" ]; then
  head_case "10 Per-IP safety net (needs ip_safety_limit == $IP_LIMIT in config)"
  rdel 'dl:ip:*'
  last=200
  for i in $(seq 1 $((IP_LIMIT + 1))); do
    read -r JWT JTI < <(mint valid)
    req "$JWT"; last="$STATUS"
  done
  check "request > limit -> 429" 429 "$last"
fi

# ---------------------------------------------------------------------------
if [ "$RUN_REDIS_DOWN" = "1" ]; then
  head_case "13 Redis unavailable -> verification degrades open"
  $DC stop "$REDIS_SVC" >/dev/null 2>&1
  read -r JWT JTI < <(mint valid)
  req "$JWT"; check "no redis -> 200" 200 "$STATUS"
  $DC start "$REDIS_SVC" >/dev/null 2>&1
fi

printf '\n\033[1m== %d passed, %d failed ==\033[0m\n' "$PASS" "$FAIL"
[ "$FAIL" -eq 0 ]
