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

include '../../vendor/autoload.php';

require_once '../../library/Flooer/Application.php';

$application = new Flooer_Application(
    array(
        'baseDir'          => '../../api_application',
        'memoryLimit'      => '512M',
        'maxExecutionTime' => 660,
        'socketTimeout'    => 600,
        'autoloadConfig'   => array(
            'register' => false,
        ),
    )
);

switch (strtolower(getenv('APPLICATION_ENV'))) {
    case 'debug':
        $application->setConfig('environment', 'debug');
        break;
    case 'development':
        $application->setConfig('environment', 'development');
        break;
    case 'production':
    default:
        $application->setConfig('environment', 'production');
        break;
}

$application->run();
