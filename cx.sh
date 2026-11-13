#!/bin/bash
#================================================================
# LOCAL maintenance assistant for Neon Pong — cx.sh
#
#   ./cx.sh            # interactive menu
#   ./cx.sh 20         # run an option directly
#   ./cx.sh 33 "about" # …with a second parameter
#
# ⚠ Runs on your LOCAL machine only. Its server counterpart is deploy.sh.
# Settings are at the top of the script (overridable via CX_* env vars).
#================================================================
cd "$(dirname "$0")"

# Settings — edit here, or override per run with env vars, e.g.
#   CX_REMOTE=me@host ./cx.sh 22
# CX_REMOTE defaults to the bare hostname, so the SSH user comes from
# ~/.ssh/config (Host pong.nikniq.com / User ...) or your local username.
PROJECT="${CX_PROJECT:-pong.nikniq.com}"
REMOTE="${CX_REMOTE:-$PROJECT}"
REMOTE_HOST="${REMOTE#*@}"
REMOTE_PORT="${CX_PORT:-18765}"              # SiteGround SSH port
REMOTE_APP="${CX_APP_DIR:-www/$PROJECT}"     # app root on the server, relative to ~
APP_URL="${CX_URL:-https://$PROJECT}"
APP_SSH="ssh -p $REMOTE_PORT"
APP_RSYNC_SSH="$APP_SSH"

if [ "$(hostname -s 2>/dev/null)" = "${REMOTE_HOST%%.*}" ]; then
  echo "⚠ This looks like $REMOTE_HOST — cx.sh is the LOCAL script. Use deploy.sh here."
  exit 1
fi

#---------------------------------------------------------------
# Helpers
#---------------------------------------------------------------
log_history() {
  mkdir -p ./log
  printf '%s  %s\n' "$(date '+%Y-%m-%d %H:%M')" "$1" >> ./log/history.log
}
remote()     { $APP_SSH    "$REMOTE" "cd ~/$REMOTE_APP && $*"; }
remote_tty() { $APP_SSH -t "$REMOTE" "cd ~/$REMOTE_APP && $*"; }
need_param() { [ -n "$PARAM2" ] || read -r -p "$1: " PARAM2; }
banner()     { echo "==========================================================="; echo "$1"; echo "==========================================================="; }
confirm() {
  echo "⚠ $1"
  read -r -p "Type 'yes' to continue: " reply
  [ "$reply" = "yes" ] || { echo "Skipped $SELECTION."; exit 0; }
}
check_pushed() {
  if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    echo "⚠ Uncommitted local changes — these will NOT deploy:"
    git status --short; echo
  fi
  UNPUSHED=$(git log --oneline @{u}.. 2>/dev/null)
  if [ -n "$UNPUSHED" ]; then
    echo "⚠ Commits not pushed — these will NOT deploy:"
    echo "$UNPUSHED"; echo
    confirm "Deploy anyway?"
  fi
  echo "Local HEAD : $(git log --oneline -1)"; echo
}

#---------------------------------------------------------------
# Menu
#---------------------------------------------------------------
if [ -z "$1" ]; then
  clear
  echo "============================================================="
  echo "Neon Pong — acting on $PROJECT ($REMOTE:~/$REMOTE_APP)"
  echo "-------------------------------------------------------------"
  echo "ROUTINE"
  echo "20 : DEPLOY: build assets, push, then pull + caches on server"
  echo "21 : ASSETS: rsync public_html/build directly (skip git)"
  echo "22 : WHAT'S LIVE: compare server HEAD with local"
  echo "24 : CACHES: rebuild config/routes/views"
  echo "25 : CACHES: clear everything"
  echo "26 : COMPOSER: install production dependencies"
  echo "DIAGNOSE"
  echo "30 : HEALTH: $APP_URL and /up"
  echo "31 : LOGS: tail the Laravel log"
  echo "32 : LOGS: recent errors"
  echo "33 : ARTISAN: run a command"
  echo "34 : CONFIG: key settings (secrets hidden)"
  echo "35 : DISK: usage"
  echo "LEADERBOARD"
  echo "40 : LEADERBOARD: show the live JSON"
  echo "41 : LEADERBOARD: download a backup to ./backups"
  echo "42 : LEADERBOARD: reset  [discards all scores]"
  echo "MAINTENANCE"
  echo "72 : MAINTENANCE MODE: on  [site goes down]"
  echo "73 : MAINTENANCE MODE: off"
  echo "74 : LOGS: truncate the Laravel log"
  echo "ACCESS"
  echo "00 : CONNECT: ssh to $REMOTE"
  echo "qq : Quit"
  echo "============================================================="
  read -r -n 2 SELECTION; echo
else
  SELECTION=$1
fi
PARAM2="${2:-}"

case "$SELECTION" in
  "20" )
  banner "DEPLOY: $PROJECT"
  # Assets are built here and committed; the server never runs npm.
  npm run build || exit 1
  if [ -n "$(git status --porcelain -- public_html/build)" ]; then
    git add public_html/build && git commit -q -m "Build assets" && git push -q && echo "✓ committed + pushed fresh build"
  fi
  check_pushed
  remote_tty "./deploy.sh"
  log_history "Deployed $PROJECT"
  ;;

  "21" )
  banner "ASSETS: build + upload"
  # For hosts without node: build here, push the gitignored build dir.
  npm run build || exit 1
  rsync -az --delete -e "$APP_RSYNC_SSH" public_html/build/ "$REMOTE:~/$REMOTE_APP/public_html/build/"
  remote "php artisan view:clear"
  log_history "Uploaded assets to $PROJECT"
  ;;

  "22" )
  banner "WHAT'S LIVE on $PROJECT"
  echo "--- server ---"; remote "git log --oneline -3 && git status --short"
  echo "--- local ---";  git log --oneline -3; echo
  LOCAL_HEAD=$(git rev-parse HEAD)
  SERVER_HEAD=$(remote "git rev-parse HEAD" 2>/dev/null | tr -d '\r')
  if [ "$LOCAL_HEAD" = "$SERVER_HEAD" ]; then echo "✓ server matches local HEAD"
  else echo "✗ server is NOT on local HEAD"; echo "  local : $LOCAL_HEAD"; echo "  server: $SERVER_HEAD"; fi
  ;;

  "24" ) banner "CACHES: rebuild"; remote "php artisan optimize"; log_history "Rebuilt caches" ;;
  "25" ) banner "CACHES: clear";   remote "php artisan optimize:clear"; log_history "Cleared caches" ;;
  "26" ) banner "COMPOSER: install"; remote_tty "composer install --no-dev --optimize-autoloader --no-interaction"; log_history "composer install" ;;

  "30" )
  banner "HEALTH: $APP_URL"
  curl -sS -o /dev/null -w 'status : %{http_code}\ntotal  : %{time_total}s\n' --max-time 25 "$APP_URL" || echo "✗ no response"
  echo "--- /up ---"
  curl -sS -o /dev/null -w 'status : %{http_code}\n' --max-time 25 "$APP_URL/up" || echo "✗ no response"
  ;;

  "31" ) banner "LOGS: tail (Ctrl-C to stop)"; remote_tty "tail -n 40 -f storage/logs/laravel.log" ;;
  "32" )
  banner "LOGS: recent errors"
  remote "grep -nE '\.(ERROR|CRITICAL|ALERT|EMERGENCY):' storage/logs/laravel.log | tail -n 25" || echo "No errors (or no log yet)."
  ;;

  "33" )
  banner "ARTISAN on $PROJECT"
  need_param "artisan command (e.g. 'about')"
  remote_tty "php artisan $PARAM2"
  log_history "artisan $PARAM2"
  ;;

  "34" )
  banner "CONFIG on $PROJECT"
  remote "grep -E '^(APP_ENV|APP_DEBUG|APP_URL|SESSION_DRIVER|CACHE_STORE|LOG_LEVEL|LEADERBOARD_[A-Z_]*)=' .env | sort"
  echo "--- keys present (values hidden) ---"
  remote "grep -oE '^[A-Z_]*(KEY|SECRET|TOKEN|PASSWORD)[A-Z_]*=' .env | tr -d '=' | sed 's/^/  /'"
  ;;

  "35" ) banner "DISK"; remote "du -sh . ; du -sh */ 2>/dev/null | sort -rh | head -10" ;;

  "40" ) banner "LEADERBOARD"; remote "cat storage/app/leaderboard.json 2>/dev/null || echo 'No scores yet.'" ;;
  "41" )
  banner "LEADERBOARD: backup"
  mkdir -p ./backups
  OUT="./backups/leaderboard-$(date '+%Y%m%d-%H%M%S').json"
  remote "cat storage/app/leaderboard.json" > "$OUT" && echo "✓ saved $OUT"
  log_history "Backed up leaderboard to $OUT"
  ;;
  "42" )
  banner "LEADERBOARD: reset"
  confirm "This deletes every score on $PROJECT (back up first with 41)."
  remote "rm -f storage/app/leaderboard.json"
  log_history "Reset leaderboard on $PROJECT"
  ;;

  "72" ) confirm "Site goes down until option 73."; remote "php artisan down"; log_history "Maintenance on" ;;
  "73" ) remote "php artisan up"; log_history "Maintenance off" ;;
  "74" ) confirm "Discard the Laravel log on $PROJECT."; remote ": > storage/logs/laravel.log"; log_history "Truncated log" ;;

  "00" ) $APP_SSH -t "$REMOTE" "cd ~/$REMOTE_APP && exec \$SHELL -l" ;;
  "qq" ) exit 0 ;;
  * ) echo "Unknown option: $SELECTION"; exit 1 ;;
esac
