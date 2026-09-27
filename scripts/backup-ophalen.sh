#!/usr/bin/env bash
# Haalt de back-ups van prd op naar een map BUITEN zxcs (eigen machine of NAS), zodat een
# storing of ramp bij zxcs niet ook de back-up raakt. Draai dagelijks, na scripts/backup.sh op
# de server (bv. via Windows Taakplanner met Git Bash, of cron op de NAS).
#
#   bash scripts/backup-ophalen.sh [doelmap]      # standaard: ~/Backups/boxtracker
#
# Gebruikt de SSH-alias `boxtrackernl` uit ~/.ssh/config. Waarschuwt als de nieuwste back-up
# ouder is dan 2 dagen (dan draait de cronjob op de server niet).
set -euo pipefail

DOEL="${1:-$HOME/Backups/boxtracker}"
mkdir -p "$DOEL"

# Alleen wat er nog niet is (geen rsync nodig; werkt ook in Git Bash op Windows).
nieuw=0
for f in $(ssh boxtrackernl 'cd backups/boxtracker && ls db-*.sql.gz uploads-*.tar.gz 2>/dev/null'); do
    if [ ! -f "$DOEL/$f" ]; then
        scp -pq "boxtrackernl:backups/boxtracker/$f" "$DOEL/$f.tmp" && mv "$DOEL/$f.tmp" "$DOEL/$f"
        nieuw=$((nieuw + 1))
    fi
done
scp -pq boxtrackernl:backups/boxtracker/LAATSTE "$DOEL/LAATSTE"
echo "$nieuw nieuwe bestanden opgehaald."

laatste="$(cat "$DOEL/LAATSTE" 2>/dev/null || echo onbekend)"
nieuwste="$(ls -t "$DOEL"/db-*.sql.gz 2>/dev/null | head -1 || true)"
if [ -z "$nieuwste" ] || [ -n "$(find "$nieuwste" -mtime +2)" ]; then
    echo "LET OP: geen verse back-up (laatste: $laatste). Draait de cronjob op de server nog?" >&2
    exit 1
fi
echo "Opgehaald naar $DOEL (laatste back-up: $laatste)"
