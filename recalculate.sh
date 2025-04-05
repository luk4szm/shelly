#!/bin/bash

start_date="2025-02-16"
end_date=$(date -d "yesterday" +%Y-%m-%d)

start_timestamp=$(date -d "$start_date" +%s)
end_timestamp=$(date -d "$end_date" +%s)

current_timestamp="$start_timestamp"

while [[ "$current_timestamp" -le "$end_timestamp" ]]; do
  current_date=$(date -d "@$current_timestamp" +%Y-%m-%d)
  php bin/console app:create:daily-stats "$current_date"
  current_timestamp=$((current_timestamp + 86400)) # Dodaj 1 dzień (86400 sekund)
done
