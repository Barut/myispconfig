#!/bin/sh

PATH=/sbin:/usr/sbin:/bin:/usr/bin:/usr/local/sbin:/usr/local/bin:/usr/X11R6/bin

. /etc/profile

cd /usr/local/ispconfig/server
/usr/bin/php -q /usr/local/ispconfig/server/server.php
