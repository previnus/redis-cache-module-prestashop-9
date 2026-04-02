<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/** @var Rediscache $module */
function upgrade_module_1_1_0($module)
{
    if (method_exists($module, 'migrateLegacyConfiguration')) {
        $module->migrateLegacyConfiguration();
    }

    if (method_exists($module, 'installCacheOverride') && !$module->installCacheOverride()) {
        return false;
    }

    if (method_exists($module, 'unregisterHook')) {
        $module->unregisterHook('actionPerformancePagecachingSave');
        $module->unregisterHook('displayBackOfficeHeader');
    }

    return true;
}
