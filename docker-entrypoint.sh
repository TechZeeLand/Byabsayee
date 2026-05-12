#!/bin/sh
# Named volumes are created owned by root. PHP-FPM workers run as www-data
# and can't write to them. Fix ownership here — this runs after Docker mounts
# the volumes, which is the only point where we can set permissions on them.

chown -R www-data:www-data \
    /Sites/byabsayee/storage/sessions \
    /Sites/byabsayee/uploads

exec php-fpm
