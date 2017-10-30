#!/bin/sh

cd "$( cd `dirname $0` && pwd )/.."

if [ ! -z "`command -v composer`" ] ; then
  composer install --prefer-dist --no-interaction
  composer dumpautoload -o
else
  [ ! -e "composer.phar" ] && php -r "readfile('https://getcomposer.org/installer');" | php
  php composer.phar install --prefer-dist --no-interaction
  php composer.phar dumpautoload -o
fi

php bin/console cache:clear --no-warmup --env=prod
php bin/console cache:warmup --env=prod