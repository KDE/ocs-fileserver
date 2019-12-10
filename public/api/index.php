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

require_once '../../library/Flooer/Application.php';
require_once '../../library/readerepub/readerepub.php';

$application = new Flooer_Application(array(
    'baseDir' => '../../api_application',
    'memoryLimit' => '512M',
    'maxExecutionTime' => 660,
    'socketTimeout' => 600
));

switch (strtolower($_SERVER['SERVER_NAME'])) {
    case 'localhost':
        $application->setConfig('environment', 'debug');
        break;
    case 'cc.ppload.com':
        $application->setConfig('environment', 'development');
        break;
    case 'www.ppload.com':
        // Continue to default
    case 'dl.opendesktop.org':
        // Continue to default
    default:
        $application->setConfig('environment', 'production');
        break;
}

$application->run();
