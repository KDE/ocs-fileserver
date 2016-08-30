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

if (empty($_GET['name'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
    exit('Forbidden');
}
if (!is_file($config['filesDir'] . '/' . $_GET['name'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    exit('File Not Found');
}

if (!is_file($config['torrentsDir'] . '/' . $_GET['name'] . '.torrent')) {
    PHPTracker_Autoloader::register();
    $core = new PHPTracker_Core(new PHPTracker_Config_Simple(array(
        'persistence' => new PHPTracker_Persistence_Mysql(
            new PHPTracker_Config_Simple($config['db'])
        ),
        'announce' => array($config['announceUri'])
    )));
    file_put_contents(
        $config['torrentsDir'] . '/' . $_GET['name'] . '.torrent',
        $core->createTorrent($config['filesDir'] . '/' . $_GET['name'], 524288)
    );
}

header('Content-Type: application/x-bittorrent');
header('Content-Disposition: attachment; filename="' . $_GET['name'] . '.torrent"');
echo file_get_contents($config['torrentsDir'] . '/' . $_GET['name'] . '.torrent');
