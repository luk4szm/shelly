#!/bin/bash

if git pull -f | grep -q "Already up to date."; then
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
