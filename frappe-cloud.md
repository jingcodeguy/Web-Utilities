> Because the file will be uploaded to the Ram drive /uploads, so any upload will be delete after shutting down the computer. 

## backup.sh
dump the exported fixtures and zip+upload to local drive through 
```bash
#!/bin/bash
set -euo pipefail

# === Usage ===
# ./extract_fixtures.sh [NGROK_SESSION]
# e.g. ./extract_fixtures.sh https://myserver.com

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 [NGROK_SESSION]"
  echo "Example: $0 3e27ca8af6f6"
  echo "final link: https://3e27ca8af6f6.ngrok-free.app/erpnext_template/cloud_frappe/upload.php"
  exit 1
fi

NGROK_SESSION="${1:-3e27ca8af6f6}"  # 可自定義 upload URL，否則用預設

TMP_DIR="$HOME/frappe-bench/tmp"
APP_FIXTURES_DIR="$HOME/frappe-bench/apps/badge_station/badge_station/fixtures"

bench --site jasmine.s.frappe.cloud export-fixtures --app badge_station

mkdir -p "$TMP_DIR"

FILE="badge_station_fixtures_$(date +%F_%H%M).tgz"
OUT="$TMP_DIR/$FILE"

tar -czf "$OUT" -C "$APP_FIXTURES_DIR" .

# upload
curl -sS -f -F "file=@$OUT" "https://${NGROK_SESSION}.ngrok-free.app/erpnext_template/cloud_frappe/upload.php"

printf '%s\n' "$FILE"
```

## dump.sh
dump the whole database and zip+upload to local drive through 
```bash
#!/bin/bash
set -euo pipefail

# === Usage ===
# ./extract_fixtures.sh [NGROK_SESSION]
# e.g. ./extract_fixtures.sh https://myserver.com

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 [NGROK_SESSION]"
  echo "Example: $0 3e27ca8af6f6"
  echo "final link: https://3e27ca8af6f6.ngrok-free.app/erpnext_template/cloud_frappe/upload.php"
  exit 1
fi

NGROK_SESSION="${1:-3e27ca8af6f6}"  #  ^o  ^g   ^z    upload URL  ^l ^p  ^i^g ^t   ^p

TMP_DIR="$HOME/frappe-bench/tmp"
BACKUP_DIR="$HOME/frappe-bench/sites/jasmine.s.frappe.cloud/private/backups"

bench --site jasmine.s.frappe.cloud backup

mkdir -p "$TMP_DIR"

FILE="jasmine_database_dump_$(date +%F_%H%M).tgz"
OUT="$TMP_DIR/$FILE"

tar -czf "$OUT" -C "$BACKUP_DIR" .
```