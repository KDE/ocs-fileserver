<?php
/**
 *
 *  ocs-fileserver
 *
 *  Copyright 2016 by pling GmbH.
 *
 *  This file is part of ocs-fileserver.
 *
 *  ocs-fileserver is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  ocs-fileserver is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 */

class RedisCache
{
    const NAMESPACE_SEPARATOR = ':_';
    /** @var int|mixed */
    private $ttl;
    /** @var mixed|string */
    private $namespace;
    /** @var Redis */
    private $redisCache;

    public function __construct($config = null)
    {
        if (!extension_loaded('redis')) {
            throw new Exception("Redis extension is not loaded");
        }

        $hostname = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        $this->ttl = $config['ttl'] ?? null;
        $this->namespace = isset($config['namespace']) ? $config['namespace'] . self::NAMESPACE_SEPARATOR : '';
        $password = $config['password'] ?? null;
        $database = $config['database'] ?? null;

        $this->redisCache = new Redis();
        //Connecting to Redis
        if (false === $this->redisCache->connect($hostname, $port)) {
            throw new Exception("Redis connection could not instantiated");
        }

        if ($password) {
            $this->redisCache->auth($password);
        }

        $resultPing = $this->redisCache->ping();
        if (!$resultPing) {
            throw new Exception("Redis connection is inaccessible: {$resultPing}");
        }
        if ($database) {
            $this->redisCache->select($database);
        }
        //$this->redisCache->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
    }

    public function get($key)
    {
        return unserialize($this->redisCache->get($this->namespace . $key));
    }

    public function set($key, $value, $ttl = null)
    {
        return $this->redisCache->set($this->namespace . $key, serialize($value), $ttl);
    }

    public function has($key)
    {
        return $this->redisCache->exists($this->namespace . $key);
    }

}