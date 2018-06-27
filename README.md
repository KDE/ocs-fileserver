# ocs-fileserver

LICENSE: GNU AGPLv3+

Copyright 2016 by pling GmbH.


## Dependencies

* apache2
* mysql-server
* php/php5
* php-mysql/php5-mysql
* php-curl/php5-curl
* php-gd/php5-gd
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


## Documents

Please see docs/* for more information.
