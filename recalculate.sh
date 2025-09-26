#!/bin/bash

# Domyślna data startu (jeśli nie podano parametru)
start_date="2025-02-16"
end_date=$(date -d "yesterday" +%Y-%m-%d)

# Jeśli został podany parametr, spróbuj go użyć jako daty startowej
if [[ -n "$1" ]]; then
  # Sprawdź format/ważność daty przy użyciu date -d
  if ! date -d "$1" >/dev/null 2>&1; then
    echo "Błędna data: '$1'. Użyj formatu RRRR-MM-DD, np. 2025-05-30."
    exit 1
  fi
  start_date="$1"
fi

start_timestamp=$(date -d "$start_date" +%s)
end_timestamp=$(date -d "$end_date" +%s)

current_timestamp="$start_timestamp"

while [[ "$current_timestamp" -le "$end_timestamp" ]]; do
  current_date=$(date -d "@$current_timestamp" +%Y-%m-%d)
  php bin/console app:create:daily-stats "$current_date"
  current_timestamp=$((current_timestamp + 86400)) # Dodaj 1 dzień (86400 sekund)
done
