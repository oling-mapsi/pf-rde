#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REMOTE_HOST="${REMOTE_HOST:-ubuntu@54.37.158.0}"
REMOTE_APP_DIR="${REMOTE_APP_DIR:-/var/www/rdg.oling.fr}"
REMOTE_URLS=(
  "https://sigr.routesdeguadeloupe.fr/"
  "https://sigr.routesdeguadeloupe.fr/connexion"
  "https://sigr.routesdeguadeloupe.fr/donnees-cartes"
)

BRANCH="main"
COMMIT_MESSAGE=""
DRY_RUN=0
SKIP_COMMIT=0
SKIP_PUSH=0
SKIP_REMOTE=0

log() {
  printf '\n[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

fail() {
  printf '\n[ERROR] %s\n' "$*" >&2
  exit 1
}

run() {
  printf '+ %s\n' "$*"
  if [[ "$DRY_RUN" -eq 0 ]]; then
    "$@"
  fi
}

usage() {
  cat <<'EOF'
Usage:
  bash bin/deploy-prod.sh -m "commit message"

Options:
  -m, --message       Commit message local git
  -b, --branch        Branch to push and deploy (default: main)
      --dry-run       Show commands without executing them
      --skip-commit   Skip git add/commit
      --skip-push     Skip git push
      --skip-remote   Skip remote deploy
  -h, --help          Show help
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    -m|--message)
      COMMIT_MESSAGE="${2:-}"
      shift 2
      ;;
    -b|--branch)
      BRANCH="${2:-}"
      shift 2
      ;;
    --dry-run)
      DRY_RUN=1
      shift
      ;;
    --skip-commit)
      SKIP_COMMIT=1
      shift
      ;;
    --skip-push)
      SKIP_PUSH=1
      shift
      ;;
    --skip-remote)
      SKIP_REMOTE=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      fail "Unknown option: $1"
      ;;
  esac
done

command -v git >/dev/null 2>&1 || fail "git missing"
command -v php >/dev/null 2>&1 || fail "php missing"
command -v curl >/dev/null 2>&1 || fail "curl missing"
command -v ssh >/dev/null 2>&1 || fail "ssh missing"

cd "$APP_DIR"

[[ -f composer.json ]] || fail "Run this script from the project repo"
[[ -x bin/console ]] || fail "bin/console missing"

CURRENT_BRANCH="$(git branch --show-current)"
[[ -n "$CURRENT_BRANCH" ]] || fail "Cannot detect current git branch"

if [[ "$CURRENT_BRANCH" != "$BRANCH" ]]; then
  fail "Current branch is '$CURRENT_BRANCH', expected '$BRANCH'"
fi

if [[ "$SKIP_COMMIT" -eq 0 && -z "$COMMIT_MESSAGE" ]]; then
  fail "Commit message required unless --skip-commit is used"
fi

log "Preflight"
run git status --short
run git fetch origin "$BRANCH"

if [[ "$DRY_RUN" -eq 0 ]]; then
  git diff --quiet "origin/$BRANCH"...HEAD || fail "Local branch differs from origin/$BRANCH. Rebase/merge first."
fi

log "Local verification"
while IFS= read -r php_file; do
  [[ -n "$php_file" ]] || continue
  [[ -f "$php_file" ]] || continue
  run php -l "$php_file"
done < <(git status --porcelain | awk '{print $2}' | grep -E '\.php$' || true)
run php bin/console importmap:install --no-interaction
run php bin/console asset-map:compile --no-interaction

if [[ "$SKIP_COMMIT" -eq 0 ]]; then
  log "Git add / commit"
  run git add -A

  if [[ "$DRY_RUN" -eq 0 ]]; then
    git diff --cached --quiet && fail "Nothing staged to commit"
  fi

  run git commit -m "$COMMIT_MESSAGE"
fi

if [[ "$SKIP_PUSH" -eq 0 ]]; then
  log "Git push"
  run git push origin "$BRANCH"
fi

if [[ "$SKIP_REMOTE" -eq 0 ]]; then
  log "Remote deploy on $REMOTE_HOST"
  REMOTE_SCRIPT=$(cat <<EOF
set -Eeuo pipefail
APP_DIR="$REMOTE_APP_DIR"
BRANCH="$BRANCH"
cd "\$APP_DIR/current"

echo "[remote] pwd: \$(pwd)"
git status --short
git rev-parse --short HEAD

TS=\$(date -u +%Y%m%d%H%M%S)
sudo -u postgres pg_dump -Fc -f /tmp/pf_rde_pre_update_\$TS.dump pf_rde
sudo mv /tmp/pf_rde_pre_update_\$TS.dump /root/pf_rde_pre_update_\$TS.dump
echo "[remote] Backup: /root/pf_rde_pre_update_\$TS.dump"

git fetch origin "\$BRANCH"
git reset --hard "origin/\$BRANCH"

composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts
composer dump-autoload --optimize --no-interaction

sudo chown -R ubuntu:www-data .
sudo chmod -R g+rwX .
sudo chown -R www-data:www-data var

sudo -u www-data env APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction
sudo -u www-data env APP_ENV=prod php bin/console importmap:install --no-interaction
sudo -u www-data env APP_ENV=prod php bin/console asset-map:compile --no-interaction
sudo -u www-data env APP_ENV=prod php bin/console cache:clear --no-warmup
sudo -u www-data env APP_ENV=prod php bin/console cache:warmup

sudo systemctl reload apache2
EOF
)

  printf '+ ssh %s %q\n' "$REMOTE_HOST" "$REMOTE_SCRIPT"
  if [[ "$DRY_RUN" -eq 0 ]]; then
    ssh "$REMOTE_HOST" "$REMOTE_SCRIPT"
  fi

  log "HTTP checks"
  for url in "${REMOTE_URLS[@]}"; do
    run curl -kfsSIL "$url"
  done
fi

log "Done"
