<?php

/**
 * ocs-fileserver
 *
 * Copyright 2016 by pling GmbH.
 *
 * This file is part of ocs-fileserver.
 *
 * ocs-fileserver is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * ocs-fileserver is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
 **/

require_once './config.php';
require_once '../../library/PHPTracker/Autoloader.php';

PHPTracker_Autoloader::register();

$core = new PHPTracker_Core(new PHPTracker_Config_Simple(array(
    'persistence' => new PHPTracker_Persistence_Mysql(
        new PHPTracker_Config_Simple($config['db'])
    ),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'interval' => 60
)));

echo $core->announce(new PHPTracker_Config_Simple($_GET));
