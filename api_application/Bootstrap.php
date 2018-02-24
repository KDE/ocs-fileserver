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

class Bootstrap extends Flooer_Application_Bootstrap
{

    public function initAppConfig()
    {
        $this->getApplication()->setResource(
            'appConfig',
            (object) parse_ini_file('configs/application.ini', true)
        );
    }

    public function initDispatch()
    {
        parent::initDispatch();
        require_once 'controllers/BaseController.php';
        $dispatch = $this->getApplication()->getResource('dispatch');
        $dispatch->setLimitMethod(false);
        $dispatch->setRenderErrorPage(false);
        $dispatch->setViewFileSuffix('.xml');
    }

    public function initRequest()
    {
        parent::initRequest();
        $request = $this->getApplication()->getResource('request');
        $request->mapUri();
    }

    public function initResponse()
    {
        parent::initResponse();
        $response = $this->getApplication()->getResource('response');
        $appConfig = $this->getApplication()->getResource('appConfig');
        $response->setHeader(
            'Access-Control-Allow-Origin',
            $appConfig->security['accessControlAllowOrigin']
        );
        $response->setHeader(
            'Access-Control-Allow-Credentials',
            $appConfig->security['accessControlAllowCredentials']
        );
        $response->setHeader(
            'Access-Control-Allow-Methods',
            $appConfig->security['accessControlAllowMethods']
        );
        $response->setHeader(
            'Access-Control-Allow-Headers',
            $appConfig->security['accessControlAllowHeaders']
        );
        $response->setHeader(
            'Access-Control-Max-Age',
            time() + 60 * 60 * 24 * $appConfig->security['accessControlMaxAge']
        );
    }

    public function initCookie()
    {
        // Do not init
    }

    public function initSession()
    {
        // Do not init
    }

    public function initGettext()
    {
        // Do not init
    }

    public function initLog()
    {
        parent::initLog();
        $log = $this->getApplication()->getResource('log');
        $appConfig = $this->getApplication()->getResource('appConfig');
        $log->setFile($appConfig->log['file']);
        $log->setMail($appConfig->log['mail']);
    }

    public function initDb()
    {
        try {
            $db = new Flooer_Db(parse_ini_file('configs/database.ini', true));
            $this->getApplication()->setResource('db', $db);
        }
        catch (Exception $exception) {
            $response = $this->getApplication()->getResource('response');
            $log = $this->getApplication()->getResource('log');
            $log->log($exception->getMessage(), LOG_ALERT);
            $response->setStatus(500);
            $response->setHeader('Content-Type', 'text/plain');
            $response->setBody('Internal server error');
            $response->send();
            exit;
        }
    }

    public function initModels()
    {
        require_once 'models/BaseModel.php';
        require_once 'models/ModelContainer.php';
        $db = $this->getApplication()->getResource('db');
        $models = new ModelContainer(
            $db,
            parse_ini_file('configs/models.ini', true)
        );
        $this->getApplication()->setResource('models', $models);
    }

}
