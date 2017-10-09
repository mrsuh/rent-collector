#!/bin/sh

directory="$( cd `dirname $0` && pwd )/../.."

(
    flock -n 9 || exit 1

    php ${directory}/bin/console app:collect --env=prod --type=vk

) 9>~/var/tmp/`basename $0`