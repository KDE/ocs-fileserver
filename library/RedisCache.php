<?php


class RedisCache
{
    const NAMESPACE_SEPARATOR = ':_';
    /**
     * @var int|mixed
     */
    private $ttl;
    /**
     * @var mixed|string
     */
    private $namespace;
    /**
     * @var Redis
     */
    private $redisCache;

    public function __construct($config = null)
    {
        if (! extension_loaded('redis')) {
            throw new Exception("Redis extension is not loaded");
        }

        $hostname = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = isset($config['port']) ? $config['port'] : 6379;
        $this->ttl = isset($config['ttl']) ? $config['ttl'] : null;
        $this->namespace = isset($config['namespace']) ? $config['namespace'] . self::NAMESPACE_SEPARATOR : '';
        $password = isset($config['password']) ? $config['password']: null;

        $this->redisCache = new Redis();
        //Connecting to Redis
        if (false === $this->redisCache->connect($hostname, $port)) {
            throw new Exception("Redis connection could not instantiated");
        }

        if ($password) {
            $this->redisCache->auth('password');
        }

        $resultPing = $this->redisCache->ping();
        if (!$resultPing) {
            throw new Exception("Redis connection is inaccessible: {$resultPing}");
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