#!/bin/bash

git pull -f
php bin/console d:s:u --dump-sql --force
php bin/console cache:clear
