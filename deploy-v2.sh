#!/usr/bin/env bash
# Deploy Boxtracker v2 naar app.boxtracker.nl (web0171, SSH-alias `boxtrackernl`).
#
#   bash deploy-v2.sh
#
# Wat het doet:
# - pakt de gecommitte HEAD (git archive — niet-gecommitte wijzigingen gaan dus nooit mee);
# - zet die op de server in een tijdelijke map en synct met rsync --delete naar de app-map,
#   met uitzondering van .env, writable/ en vendor/ (die leven op de server);
# - draait composer install --no-dev op de server.
#
# Het schema gaat NIET automatisch mee: wijzigingen in sql/schema.sql apart live draaien
# (zie CLAUDE.md). v1 (boxtracker.minisaas.nl) deployt via deploy.php vanaf branch `familie`.
set -euo pipefail

HOST=boxtrackernl
APP_DIR='domains/boxtracker.nl/public_html/app'
cd "$(dirname "${BASH_SOURCE[0]}")"

branch=$(git rev-parse --abbrev-ref HEAD)
if [ "$branch" != "master" ]; then
    echo "Geweigerd: v2 deployt alleen vanaf master (nu: $branch)." >&2
    exit 1
fi
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "Let op: er zijn niet-gecommitte wijzigingen — die gaan NIET mee (alleen HEAD)."
fi

rev=$(git rev-parse --short HEAD)
echo "Deploy $rev naar $HOST:~/$APP_DIR ..."

git archive --format=tar HEAD -- . \
    ':(exclude)tests' ':(exclude)sql' ':(exclude)boxtracker-ontwerp' \
    ':(exclude)handoff.md' ':(exclude)CLAUDE.md' ':(exclude)deploy-v2.sh' \
    ':(exclude)phpunit.dist.xml' ':(exclude)env' ':(exclude).gitignore' \
  | ssh "$HOST" "set -e
    TMP=\$(mktemp -d)
    trap 'rm -rf \"\$TMP\"' EXIT
    tar -mxf - -C \"\$TMP\"
    mkdir -p ~/$APP_DIR
    rsync -a --delete \
        --exclude=/.env --exclude=/writable/ --exclude=/vendor/ --exclude=/cgi-bin/ \
        \"\$TMP\"/ ~/$APP_DIR/
    cd ~/$APP_DIR
    # rsync neemt de rechten van de mktemp-map (700) over; de webserver moet erin kunnen.
    chmod 755 .
    chmod -R go-w app public system
    chmod 600 .env 2>/dev/null || true
    mkdir -p writable/cache writable/logs writable/session writable/uploads writable/mail writable/debugbar
    [ -f .env ] || echo 'WAARSCHUWING: geen .env op de server!'
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
    echo '$rev' > writable/REVISION
    echo 'Klaar: $rev staat live.'"
