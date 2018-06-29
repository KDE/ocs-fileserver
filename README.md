# ocs-fileserver

LICENSE: GNU AGPLv3+

Copyright 2016 by pling GmbH.


## Dependencies

* apache2
* libapache2-mod-php
* mysql-server
* php
* php-mysql
* php-mbstring
* php-xml
* php-curl
* php-gd
* zsync


## Installation

Create configuration files from *.sample.ini to *.ini.

* api_application/configs/application.ini
* api_application/configs/database.ini
* api_application/configs/models.ini
* api_application/configs/clients.ini

Change data directories to rewritable

* data
* data/database
* data/files
* data/logs
* data/thumbnails
* data/zsync


## Documents

Please see docs/* for more information.
