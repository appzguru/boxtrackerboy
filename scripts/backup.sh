#!/usr/bin/env bash
# Dagelijkse back-up van Boxtracker OP DE SERVER (whitelabel-plan.md stap 6):
# database (mysqldump) + foto's (writable/uploads) naar ~/backups/boxtracker/, 14 dagen bewaard.
#
# Draaien via een cronjob in het zxcs-paneel (er is geen crontab op de command line), bv. 03:15:
#   bash ~/domains/boxtracker.nl/public_html/app/scripts/backup.sh
#
# Dit is nog geen back-up "buiten de server": haal ~/backups/boxtracker/ dagelijks op naar een
# eigen machine of NAS met scripts/backup-ophalen.sh (of later rclone naar objectopslag).
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOEL="${BACKUP_DIR:-$HOME/backups/boxtracker}"
BEWAAR_DAGEN="${BACKUP_DAGEN:-14}"
STEMPEL="$(date +%Y%m%d-%H%M)"
mkdir -p "$DOEL"
chmod 700 "$DOEL"

# Databasegegevens uit de .env van de app (niet in dit script, niet op de command line).
env_waarde() {
    grep -E "^database\.default\.$1[[:space:]]*=" "$APP_DIR/.env" | head -1 \
        | sed -E "s/^[^=]*=[[:space:]]*//; s/^[\"']//; s/[\"'][[:space:]]*$//"
}
DB_HOST="$(env_waarde hostname)"
DB_NAAM="$(env_waarde database)"
DB_USER="$(env_waarde username)"
export MYSQL_PWD="$(env_waarde password)"

umask 077
DUMP="$DOEL/db-$STEMPEL.sql.gz"
mysqldump --single-transaction --quick --routines --no-tablespaces \
    -h "$DB_HOST" -u "$DB_USER" "$DB_NAAM" | gzip -9 > "$DUMP.tmp"
mv "$DUMP.tmp" "$DUMP"

if [ -d "$APP_DIR/writable/uploads" ]; then
    tar -czf "$DOEL/uploads-$STEMPEL.tar.gz.tmp" -C "$APP_DIR/writable" uploads
    mv "$DOEL/uploads-$STEMPEL.tar.gz.tmp" "$DOEL/uploads-$STEMPEL.tar.gz"
fi

# Controle: een lege of kapotte dump is erger dan geen dump (dan denk je dat het goed gaat).
if ! gzip -t "$DUMP" || [ "$(gzip -dc "$DUMP" | grep -c 'CREATE TABLE')" -lt 5 ]; then
    echo "BACK-UP MISLUKT: $DUMP is leeg of kapot" >&2
    exit 1
fi

find "$DOEL" -name 'db-*.sql.gz' -mtime +"$BEWAAR_DAGEN" -delete
find "$DOEL" -name 'uploads-*.tar.gz' -mtime +"$BEWAAR_DAGEN" -delete
echo "$STEMPEL" > "$DOEL/LAATSTE"
echo "Back-up klaar: $DUMP"
