#!/bin/bash

start_date="2025-02-16"
end_date=$(date -d "yesterday" +%Y-%m-%d)

current_date="$start_date"

while [[ "$current_date" <= "$end_date" ]]; do
  php bin/console app:create:daily-stats "$current_date"
  current_date=$(date -d "$current_date + 1 day" +%Y-%m-%d)
done
