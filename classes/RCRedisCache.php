<?php
/**
 * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'rediscache/vendor/autoload.php';

use Predis\Client;

class RCRedisCache extends Cache
{
    /** @var Client */
    protected $redis;

    protected $ip;
    protected $port;
    protected $password;
    protected $database = 0;
    protected $life_time;

    protected $blacklist = array(
        'cart',
        'cart_cart_rule',
        'cart_product',
        'connections',
        'connections_source',
        'connections_page',
        'customer',
        'customer_group',
        'customized_data',
        'guest',
        'pagenotfound',
        'page_viewed',
        'employee',
        'log',
        'ets_superspeed_cache_page',
        'ets_tc_session',
        'ets_superspeed_hook_time',
        'ets_tc_action',
        'ets_abancart_campaign',
        'ets_abancart_reminder',
        'abancart_display_tracking',
    );

    private $connected = false;

    public function __construct()
    {
        $this->connect();
    }

    public function connect()
    {
        if (!class_exists('\\Predis\\Client')) {
            return;
        }

        $ip = Configuration::get(Rediscache::CONFIG_IP);
        $port = Configuration::get(Rediscache::CONFIG_PORT);
        $password = Configuration::get(Rediscache::CONFIG_PASSWORD);
        $database = Configuration::get(Rediscache::CONFIG_DATABASE);
        $lifetime = Configuration::get(Rediscache::CONFIG_LIFETIME);

        $this->ip = $ip !== false ? (string) $ip : '127.0.0.1';
        $this->port = $port !== false ? (string) $port : '6379';
        $this->password = $password !== false ? (string) $password : '';
        $this->database = $database !== false ? (int) $database : 0;
        $this->life_time = Validate::isUnsignedInt($lifetime) && (int) $lifetime > 0 ? (int) $lifetime * 24 * 60 * 60 : null;

        $configs = array(
            'scheme' => 'tcp',
            'host' => $this->ip,
            'port' => (int) $this->port,
            'database' => (int) $this->database,
        );

        if ($this->password !== '') {
            $configs['password'] = $this->password;
        }

        $this->redis = new Client($configs);

        try {
            foreach ($this->redis->keys('*') as $key) {
                $this->keys[$key] = true;
            }

            $this->setConnected(true);
        } catch (Exception $exception) {
            @file_put_contents(_PS_ROOT_DIR_ . '/var/logs/rediscache.log', '[' . date('Y-m-d H:i:s') . '] ' . $exception->getMessage() . PHP_EOL, FILE_APPEND);
            $this->setConnected(false);
        }

        if (!is_array($this->keys)) {
            $this->keys = array();
        }
    }

    public function setConnected($connected)
    {
        $this->connected = (bool) $connected;
    }

    public function isConnected()
    {
        return $this->connected && $this->redis instanceof Client;
    }

    protected function _set($key, $value, $ttl = 0)
    {
        if (!$this->isConnected()) {
            return false;
        }

        $result = $this->redis->set($key, @json_encode($value));

        if ($ttl > 0) {
            $this->redis->expire($key, (int) $ttl);
        } elseif ((int) $this->life_time > 0) {
            $this->redis->expire($key, (int) $this->life_time);
        }

        if ($result === false && method_exists($this, 'setAdjustTableCacheSize')) {
            $this->setAdjustTableCacheSize(true);
        }

        return $result;
    }

    protected function _get($key)
    {
        if (!$this->isConnected()) {
            return false;
        }

        $result = $this->redis->get($key);

        return $result !== null ? @json_decode($result, true) : false;
    }

    protected function _exists($key)
    {
        if (!$this->isConnected()) {
            return false;
        }

        return $this->redis->exists($key) !== false;
    }

    protected function _delete($key)
    {
        if (!$this->isConnected()) {
            return false;
        }

        if ($key === '*') {
            return $this->flush();
        }

        if (strpos($key, '*') === false) {
            $this->redis->del($key);

            return true;
        }

        $pattern = str_replace('\\*', '.*', preg_quote($key, '/'));
        $keys = $this->redis->keys($pattern);

        foreach ($keys as $redisKey) {
            $this->redis->del($redisKey);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function _deleteMulti(array $keyArray)
    {
        if (!$this->isConnected()) {
            return false;
        }

        return $this->redis->del($keyArray);
    }

    protected function _writeKeys()
    {
        return $this->isConnected();
    }

    public function flush()
    {
        if (!$this->isConnected()) {
            return false;
        }

        $this->redis->flushdb();

        return true;
    }

    public static function ping($ip, $port, $password = null, $database = 0)
    {
        if (!$ip || !$port || !Validate::isUnsignedInt($port) || !Validate::isUnsignedInt($database) || (int) $database < 0 || (int) $database > 15) {
            return false;
        }

        try {
            $parameters = array(
                'host' => $ip,
                'port' => (int) $port,
                'database' => (int) $database,
            );

            if ($password !== null && $password !== '') {
                $parameters['password'] = $password;
            }

            $testRedis = new Client($parameters);

            return $testRedis->ping('pong');
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }
}
