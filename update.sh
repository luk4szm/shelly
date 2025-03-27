#!/bin/bash

if git pull -f | grep -q "Already up to date."; then
  echo "Repo aktualne!"
  exit 0
fi

php bin/console d:s:u --dump-sql --force
php bin/console cache:clear
