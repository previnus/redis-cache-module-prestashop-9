<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/** @var Rediscache $module */
function upgrade_module_1_2_0($module)
{
    // Ensure the new socket config key exists with an empty default
    if (Configuration::get(Rediscache::CONFIG_SOCKET) === false) {
        Configuration::updateValue(Rediscache::CONFIG_SOCKET, '');
    }

    // If Redis caching was already active, sync parameters.php so PS9 boots correctly
    if (method_exists($module, 'updateParametersFile')) {
        $cacheEnabled = (bool) Configuration::get('PS_CACHE_ENABLED');
        $cachingSystem = Configuration::get('PS_CACHING_SYSTEM');
        if ($cacheEnabled && $cachingSystem === 'CacheRedis') {
            $module->updateParametersFile(true);
        }
    }

    return true;
}
