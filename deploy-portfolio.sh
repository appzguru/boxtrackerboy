#!/usr/bin/env bash
# Deploy de statische portfolio-demo (portfolio/) naar portfolio.boxtracker.nl
# (web0171, SSH-alias `boxtrackernl`, documentmap public_html/portfolio).
#
#   bash deploy-portfolio.sh
#
# Gecommitte HEAD van portfolio/ via git archive, rsync --delete naar de documentmap;
# cgi-bin/ blijft staan. Raakt de landingspagina en de app niet.
set -euo pipefail

HOST=boxtrackernl
REMOTE_DIR='domains/boxtracker.nl/public_html/portfolio'
cd "$(dirname "${BASH_SOURCE[0]}")"

if [ -n "$(git status --porcelain --untracked-files=no -- portfolio)" ]; then
    echo "Let op: er zijn niet-gecommitte wijzigingen in portfolio/ — die gaan NIET mee (alleen HEAD)."
fi

rev=$(git rev-parse --short HEAD)
echo "Deploy portfolio/ @ $rev naar $HOST:~/$REMOTE_DIR ..."

git archive --format=tar HEAD -- portfolio \
  | ssh "$HOST" "set -e
    TMP=\$(mktemp -d)
    trap 'rm -rf \"\$TMP\"' EXIT
    tar -mxf - -C \"\$TMP\"
    mkdir -p ~/$REMOTE_DIR
    rsync -a --delete --exclude=/cgi-bin/ \"\$TMP\"/portfolio/ ~/$REMOTE_DIR/
    cd ~/$REMOTE_DIR
    chmod 755 .
    find . -mindepth 1 -maxdepth 1 ! -name cgi-bin -exec chmod -R go-w {} +
    echo 'Klaar: portfolio $rev staat live.'"
