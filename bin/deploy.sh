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

files[1]='app/config/black_list.description.yml'
files[2]='app/config/black_list.person.yml'
files[3]='app/config/parser.yml'

for f in "${files[@]}";  do
if [ ! -f $f ]; then
    cp $f.dist $f
fi
done

php bin/console cache:clear --no-warmup --env=prod
php bin/console cache:warmup --env=prod