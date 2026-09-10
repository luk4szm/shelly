#!/bin/bash

pull_output=$(git pull -f)
pull_status=$?
echo "$pull_output"

if [ $pull_status -ne 0 ]; then
  echo "Aktualizacja repozytorium nie powiodła się."
  exit $pull_status
fi

asset_version=$(git rev-parse --short HEAD)
if grep -q '^APP_ASSET_VERSION=' .env 2>/dev/null; then
  sed -i "s/^APP_ASSET_VERSION=.*/APP_ASSET_VERSION=$asset_version/" .env
else
  printf '\nAPP_ASSET_VERSION=%s\n' "$asset_version" >> .env
fi

echo "Wersja assetów: $asset_version"
sleep 1

if echo "$pull_output" | grep -q "Already up to date."; then
  echo "Repozytorium jest już aktualne!"
  exit 0
fi

echo "Repozytorium zaktualizowane."
sleep 1

echo "Aktualizacja schematu bazy danych..."
php bin/console doctrine:cache:clear-metadata
sleep 1
php bin/console doctrine:schema:update --dump-sql --force
sleep 1

echo "Czyszczenie pamięci podręcznej..."
php bin/console cache:clear
sleep 1

echo "Gotowe!"
