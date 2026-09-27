#!/usr/bin/env bash
# Deploy Boxtracker v2 naar de TESTOMGEVING boxtracker.minisaas.nl (web0098, SSH-alias `boxtracker`).
# Prd gaat via deploy-v2.sh (alleen master). Dit script mag vanaf elke branch.
#
#   bash deploy-dev.sh
#
# Zelfde werkwijze als deploy-v2.sh: gecommitte HEAD via git archive, rsync --delete,
# .env / writable/ / vendor/ blijven op de server, composer install daar.
# De .env op de server moet `boxtracker.omgeving = dev` bevatten (waarschuwingsbalk).
set -euo pipefail

HOST=boxtracker
APP_DIR='domains/minisaas.nl/public_html/boxtracker'
cd "$(dirname "${BASH_SOURCE[0]}")"

branch=$(git rev-parse --abbrev-ref HEAD)
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "Let op: er zijn niet-gecommitte wijzigingen — die gaan NIET mee (alleen HEAD)."
fi

rev=$(git rev-parse --short HEAD)
echo "Deploy $rev ($branch) naar DEV — $HOST:~/$APP_DIR ..."

git archive --format=tar HEAD -- . \
    ':(exclude)tests' ':(exclude)sql' ':(exclude)boxtracker-ontwerp' \
    ':(exclude)handoff.md' ':(exclude)CLAUDE.md' ':(exclude)deploy-v2.sh' ':(exclude)deploy-dev.sh' \
    ':(exclude)phpunit.dist.xml' ':(exclude)env' ':(exclude).gitignore' \
  | ssh "$HOST" "set -e
    TMP=\$(mktemp -d)
    trap 'rm -rf \"\$TMP\"' EXIT
    tar -mxf - -C \"\$TMP\"
    cd ~/$APP_DIR
    grep -q '^boxtracker.omgeving *= *dev' .env || { echo 'Geweigerd: .env op de server heeft geen boxtracker.omgeving = dev.' >&2; exit 1; }
    rsync -a --delete \
        --exclude=/.env --exclude=/.env.bak-v1 --exclude=/writable/ --exclude=/vendor/ --exclude=/cgi-bin/ \
        \"\$TMP\"/ ~/$APP_DIR/
    chmod 755 .
    chmod -R go-w app public system
    chmod 600 .env
    mkdir -p writable/cache writable/logs writable/session writable/uploads writable/mail writable/debugbar
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
    echo '$rev' > writable/REVISION
    echo 'Klaar: $rev ($branch) staat op DEV.'"
