<?php

$_rcPath = _PS_MODULE_DIR_ . 'rediscache/classes/RCRedisCache.php';

if (file_exists($_rcPath)) {
    require_once $_rcPath;
    class CacheRedis extends RCRedisCache {}
} else {
    // Module files are missing — fall back to file cache so PS9 can still boot.
    class CacheRedis extends CacheFs {}
}
