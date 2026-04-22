<?php
/**
 * 2007-2023 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2023 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
/** @var $module Rediscache */
function upgrade_module_1_0_2($module)
{
    $prFilepath = _PS_ROOT_DIR_ . '/app/config/parameters.php';

    if (!file_exists($prFilepath)) {
        return true;
    }

    $parameters = require $prFilepath;

    if (!is_array($parameters) || !isset($parameters['parameters'])) {
        return true;
    }

    $parameters['parameters']['ps_caching'] = 'CacheRedis';
    $var_export_content = sprintf('<?php return %s;', var_export($parameters, true));
    @file_put_contents($prFilepath, $var_export_content);

    if (!class_exists('CacheRedis'))
        $module->exportClassRedisCache();

    Tools::generateIndex();
    return true;
}
