#!/bin/sh

cd "$( cd `dirname $0` && pwd )/.."

dir=$(pwd)

file="$dir/app/config/crontab.dist"

if [ -f "$file" ] ; then
  cp $file $dir/app/config/crontab
  sed "s|{dir}|${dir}|g" -i $dir/app/config/crontab
fi

if [ -f "$dir/app/config/crontab" ] ; then
  crontab $dir/app/config/crontab
fi