#!/usr/bin/env bash
# Deploy de statische landingspagina (landing/) naar boxtracker.nl (web0171,
# SSH-alias `boxtrackernl`). Los van de app: raakt nooit public_html/app,
# public_html/portfolio (subdomein, eigen deploy), cgi-bin of .htaccess.
#
#   bash deploy-landing.sh
#
# Zet de gecommitte HEAD van landing/ via git archive op de server en synct
# met rsync --delete naar public_html — met app/, cgi-bin/ en .htaccess
# expliciet uitgesloten, zodat die met rust blijven.
set -euo pipefail

HOST=boxtrackernl
REMOTE_DIR='domains/boxtracker.nl/public_html'
cd "$(dirname "${BASH_SOURCE[0]}")"

branch=$(git rev-parse --abbrev-ref HEAD)
if [ "$branch" != "master" ]; then
    echo "Geweigerd: de landingspagina deployt alleen vanaf master (nu: $branch)." >&2
    exit 1
fi
if [ -n "$(git status --porcelain --untracked-files=no -- landing)" ]; then
    echo "Let op: er zijn niet-gecommitte wijzigingen in landing/ — die gaan NIET mee (alleen HEAD)."
fi

rev=$(git rev-parse --short HEAD)
echo "Deploy landing/ @ $rev naar $HOST:~/$REMOTE_DIR ..."

git archive --format=tar HEAD -- landing \
  | ssh "$HOST" "set -e
    TMP=\$(mktemp -d)
    trap 'rm -rf \"\$TMP\"' EXIT
    tar -mxf - -C \"\$TMP\"
    mkdir -p ~/$REMOTE_DIR
    rsync -a --delete \
        --exclude=/app/ --exclude=/portfolio/ --exclude=/cgi-bin/ --exclude=/.htaccess \
        \"\$TMP\"/landing/ ~/$REMOTE_DIR/
    cd ~/$REMOTE_DIR
    chmod 755 .
    find . -maxdepth 1 ! -name app ! -name portfolio ! -name cgi-bin ! -name .htaccess ! -name . -exec chmod -R go-w {} +
    echo '$rev' > .landing-revision
    echo 'Klaar: landing $rev staat live.'"
