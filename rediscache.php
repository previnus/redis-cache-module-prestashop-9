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

require_once __DIR__ . '/classes/RCRedisCache.php';

class Rediscache extends Module
{
    const CONFIG_ENABLE = 'REDISCACHE_ENABLE_CORE_CACHE';
    const CONFIG_IP = 'REDISCACHE_IP';
    const CONFIG_PORT = 'REDISCACHE_PORT';
    const CONFIG_PASSWORD = 'REDISCACHE_PASSWORD';
    const CONFIG_DATABASE = 'REDISCACHE_DATABASE';
    const CONFIG_LIFETIME = 'REDISCACHE_LIFETIME';

    public function __construct()
    {
        $this->name = 'rediscache';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'PrestaHero';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Redis Cache Pro');
        $this->description = $this->l('Configure a Redis cache provider for PrestaShop 9 using module-managed settings and override installation.');
        $this->refs = 'https://prestahero.com/';
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->migrateLegacyConfiguration();

        return $this->setDefaultConfiguration() && $this->installCacheOverride();
    }

    public function enable($force_all = false)
    {
        return parent::enable($force_all) && $this->installCacheOverride();
    }

    public function uninstall()
    {
        return $this->disableRedisCaching()
            && $this->deleteConfiguration()
            && $this->uninstallCacheOverride()
            && parent::uninstall();
    }

    public function disable($force_all = false)
    {
        return $this->disableRedisCaching()
            && $this->uninstallCacheOverride()
            && parent::disable($force_all);
    }

    public function getContent()
    {
        $output = $this->migrateLegacyConfiguration();

        if (Tools::isSubmit('submitRediscacheConfig')) {
            $output .= $this->processConfigurationForm();
        }

        if (Tools::isSubmit('submitTestRediscacheConnection')) {
            $output .= $this->processTestConnection();
        }

        if (Tools::isSubmit('submitFlushRediscache')) {
            $output .= $this->processFlushCache();
        }

        return $output . $this->renderCompatibilityNotice() . $this->renderForm() . $this->renderToolsPanel();
    }

    protected function renderCompatibilityNotice()
    {
        return $this->displayInformation(
            $this->l('PrestaShop 9 compatibility mode is active. This module now stores its settings with the PrestaShop Configuration API and installs CacheRedis through the override mechanism instead of writing directly to core files or legacy parameters.php.')
        );
    }

    protected function processConfigurationForm()
    {
        $values = $this->getPostedConfigurationValues();
        $errors = $this->validateConfigurationValues($values);

        if (!empty($errors)) {
            return $this->displayError(implode('<br>', $errors));
        }

        $this->updateConfigurationValues($values);

        if (!$this->installCacheOverride()) {
            return $this->displayError($this->l('Settings were saved, but the CacheRedis override could not be installed. Check file permissions on the PrestaShop override directory.'));
        }

        return $this->displayConfirmation($this->l('Settings updated successfully.'));
    }

    protected function processTestConnection()
    {
        $values = $this->getConfigurationValues();
        $result = RCRedisCache::ping(
            $values[self::CONFIG_IP],
            $values[self::CONFIG_PORT],
            $values[self::CONFIG_PASSWORD],
            $values[self::CONFIG_DATABASE]
        );

        if ($result === 'pong') {
            return $this->displayConfirmation($this->l('Redis is working.'));
        }

        if ($result === false) {
            return $this->displayError($this->l('Redis is not working. Check your configuration.'));
        }

        return $this->displayError(sprintf($this->l('Redis connection failed: %s'), $result));
    }

    protected function processFlushCache()
    {
        $cache = new RCRedisCache();

        if ($cache->flush()) {
            return $this->displayConfirmation($this->l('Redis cache has been cleared.'));
        }

        return $this->displayError($this->l('Unable to clear Redis cache. Make sure Redis is reachable with the saved settings.'));
    }

    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitRediscacheConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigurationValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigFormDefinition()));
    }

    protected function getConfigFormDefinition()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Redis cache settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable PrestaShop cache with Redis'),
                        'name' => self::CONFIG_ENABLE,
                        'is_bool' => true,
                        'desc' => $this->l('When enabled, the module stores PS_CACHE_ENABLED and PS_CACHING_SYSTEM using the Configuration API and installs a CacheRedis override for PrestaShop.'),
                        'values' => array(
                            array('id' => self::CONFIG_ENABLE . '_on', 'value' => 1, 'label' => $this->l('Enabled')),
                            array('id' => self::CONFIG_ENABLE . '_off', 'value' => 0, 'label' => $this->l('Disabled')),
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('IP address or domain'),
                        'name' => self::CONFIG_IP,
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Port'),
                        'name' => self::CONFIG_PORT,
                        'required' => true,
                        'class' => 'fixed-width-sm',
                    ),
                    array(
                        'type' => 'password',
                        'label' => $this->l('Authentication'),
                        'name' => self::CONFIG_PASSWORD,
                        'desc' => $this->l('Leave empty if your Redis instance does not require a password.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Database'),
                        'name' => self::CONFIG_DATABASE,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Enter a database number between 0 and 15.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Lifetime (days)'),
                        'name' => self::CONFIG_LIFETIME,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Optional. Leave empty for Redis default persistence behaviour.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitRediscacheConfig',
                ),
            ),
        );
    }

    protected function renderToolsPanel()
    {
        $configurationUrl = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $isOverrideInstalled = file_exists($this->getInstalledOverridePath());
        $redisLoaded = class_exists('Predis\\Client');

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-wrench"></i> ' . $this->l('Tools') . '</h3>';
        $html .= '<p>' . $this->l('Use these actions after saving your settings.') . '</p>';
        $html .= '<ul>';
        $html .= '<li>' . sprintf($this->l('Predis library available: %s'), $redisLoaded ? $this->l('Yes') : $this->l('No')) . '</li>';
        $html .= '<li>' . sprintf($this->l('CacheRedis override installed: %s'), $isOverrideInstalled ? $this->l('Yes') : $this->l('No')) . '</li>';
        $html .= '</ul>';
        $html .= '<form method="post" action="' . htmlspecialchars($configurationUrl, ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<button class="btn btn-default" type="submit" name="submitTestRediscacheConnection"><i class="icon-plug"></i> ' . $this->l('Test Redis') . '</button> ';
        $html .= '<button class="btn btn-default" type="submit" name="submitFlushRediscache"><i class="icon-eraser"></i> ' . $this->l('Flush Redis cache') . '</button>';
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    protected function getPostedConfigurationValues()
    {
        return array(
            self::CONFIG_ENABLE => (int) Tools::getValue(self::CONFIG_ENABLE),
            self::CONFIG_IP => trim((string) Tools::getValue(self::CONFIG_IP)),
            self::CONFIG_PORT => trim((string) Tools::getValue(self::CONFIG_PORT)),
            self::CONFIG_PASSWORD => (string) Tools::getValue(self::CONFIG_PASSWORD),
            self::CONFIG_DATABASE => trim((string) Tools::getValue(self::CONFIG_DATABASE)),
            self::CONFIG_LIFETIME => trim((string) Tools::getValue(self::CONFIG_LIFETIME)),
        );
    }

    protected function getConfigurationValues()
    {
        $cacheEnabled = Configuration::get('PS_CACHE_ENABLED');
        $cachingSystem = Configuration::get('PS_CACHING_SYSTEM');
        $ip = Configuration::get(self::CONFIG_IP);
        $port = Configuration::get(self::CONFIG_PORT);
        $password = Configuration::get(self::CONFIG_PASSWORD);
        $database = Configuration::get(self::CONFIG_DATABASE);
        $lifetime = Configuration::get(self::CONFIG_LIFETIME);

        return array(
            self::CONFIG_ENABLE => (int) ($cacheEnabled && $cachingSystem === 'CacheRedis'),
            self::CONFIG_IP => $ip !== false ? (string) $ip : '127.0.0.1',
            self::CONFIG_PORT => $port !== false ? (string) $port : '6379',
            self::CONFIG_PASSWORD => $password !== false ? (string) $password : '',
            self::CONFIG_DATABASE => $database !== false ? (string) $database : '0',
            self::CONFIG_LIFETIME => $lifetime !== false ? (string) $lifetime : '',
        );
    }

    protected function validateConfigurationValues(array $values)
    {
        $errors = array();

        if ($values[self::CONFIG_IP] === '') {
            $errors[] = $this->l('IP address or domain is required.');
        } elseif (!(filter_var($values[self::CONFIG_IP], FILTER_VALIDATE_IP) || filter_var($values[self::CONFIG_IP], FILTER_VALIDATE_DOMAIN))) {
            $errors[] = $this->l('IP address or domain is invalid.');
        }

        if ($values[self::CONFIG_PORT] === '') {
            $errors[] = $this->l('Port is required.');
        } elseif (!Validate::isUnsignedInt($values[self::CONFIG_PORT]) || (int) $values[self::CONFIG_PORT] <= 0) {
            $errors[] = $this->l('Port is invalid.');
        }

        if ($values[self::CONFIG_DATABASE] !== '' && (!Validate::isUnsignedInt($values[self::CONFIG_DATABASE]) || (int) $values[self::CONFIG_DATABASE] > 15)) {
            $errors[] = $this->l('Database is invalid. Please enter a number within the range of 0 to 15.');
        }

        if ($values[self::CONFIG_LIFETIME] !== '' && (!Validate::isUnsignedInt($values[self::CONFIG_LIFETIME]) || (int) $values[self::CONFIG_LIFETIME] <= 0)) {
            $errors[] = $this->l('Lifetime is invalid.');
        }

        return $errors;
    }

    protected function updateConfigurationValues(array $values)
    {
        Configuration::updateValue(self::CONFIG_IP, $values[self::CONFIG_IP]);
        Configuration::updateValue(self::CONFIG_PORT, $values[self::CONFIG_PORT]);
        Configuration::updateValue(self::CONFIG_PASSWORD, $values[self::CONFIG_PASSWORD]);
        Configuration::updateValue(self::CONFIG_DATABASE, $values[self::CONFIG_DATABASE] === '' ? 0 : (int) $values[self::CONFIG_DATABASE]);
        Configuration::updateValue(self::CONFIG_LIFETIME, $values[self::CONFIG_LIFETIME] === '' ? '' : (int) $values[self::CONFIG_LIFETIME]);

        if ((int) $values[self::CONFIG_ENABLE] === 1) {
            Configuration::updateValue('PS_CACHING_SYSTEM', 'CacheRedis');
            Configuration::updateValue('PS_CACHE_ENABLED', 1);
        } else {
            $this->disableRedisCaching();
        }

        return true;
    }

    protected function disableRedisCaching()
    {
        if (Configuration::get('PS_CACHING_SYSTEM') === 'CacheRedis') {
            Configuration::updateValue('PS_CACHE_ENABLED', 0);
            Configuration::updateValue('PS_CACHING_SYSTEM', 'CacheFs');
        }

        return true;
    }

    protected function setDefaultConfiguration()
    {
        $defaults = array(
            self::CONFIG_IP => '127.0.0.1',
            self::CONFIG_PORT => '6379',
            self::CONFIG_PASSWORD => '',
            self::CONFIG_DATABASE => '0',
            self::CONFIG_LIFETIME => '',
        );

        foreach ($defaults as $key => $value) {
            if (Configuration::get($key) === false) {
                Configuration::updateValue($key, $value);
            }
        }

        return true;
    }

    protected function deleteConfiguration()
    {
        $keys = array(
            self::CONFIG_ENABLE,
            self::CONFIG_IP,
            self::CONFIG_PORT,
            self::CONFIG_PASSWORD,
            self::CONFIG_DATABASE,
            self::CONFIG_LIFETIME,
        );

        foreach ($keys as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }

    public function migrateLegacyConfiguration()
    {
        $legacyParameters = $this->getLegacyParameters();

        if (empty($legacyParameters)) {
            return '';
        }

        $updated = false;

        $mapping = array(
            'redis_cache_ip' => self::CONFIG_IP,
            'redis_cache_port' => self::CONFIG_PORT,
            'redis_cache_password' => self::CONFIG_PASSWORD,
            'redis_cache_database' => self::CONFIG_DATABASE,
            'redis_cache_life_time' => self::CONFIG_LIFETIME,
        );

        foreach ($mapping as $legacyKey => $configurationKey) {
            if (array_key_exists($legacyKey, $legacyParameters) && Configuration::get($configurationKey) === false) {
                Configuration::updateValue($configurationKey, $legacyParameters[$legacyKey]);
                $updated = true;
            }
        }

        if (!empty($legacyParameters['ps_cache_enable']) && !Configuration::get('PS_CACHE_ENABLED')) {
            Configuration::updateValue('PS_CACHE_ENABLED', (int) $legacyParameters['ps_cache_enable']);
            $updated = true;
        }

        if (!empty($legacyParameters['ps_caching']) && $legacyParameters['ps_caching'] === 'CacheRedis' && Configuration::get('PS_CACHING_SYSTEM') !== 'CacheRedis') {
            Configuration::updateValue('PS_CACHING_SYSTEM', 'CacheRedis');
            $updated = true;
        }

        if ($updated) {
            return $this->displayConfirmation($this->l('Legacy Redis cache settings were migrated from parameters.php to the Configuration API.'));
        }

        return '';
    }

    protected function getLegacyParameters()
    {
        $parametersFilepath = _PS_ROOT_DIR_ . '/app/config/parameters.php';

        if (!file_exists($parametersFilepath)) {
            return array();
        }

        $parameters = require $parametersFilepath;

        if (!is_array($parameters) || !isset($parameters['parameters']) || !is_array($parameters['parameters'])) {
            return array();
        }

        return $parameters['parameters'];
    }

    public function installCacheOverride()
    {
        $sourcePath = $this->getModuleOverridePath();
        $targetPath = $this->getInstalledOverridePath();
        $targetDirectory = dirname($targetPath);

        if (!file_exists($sourcePath)) {
            return false;
        }

        if (!is_dir($targetDirectory) && !@mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            return false;
        }

        if (!@copy($sourcePath, $targetPath)) {
            return false;
        }

        return $this->refreshClassIndex();
    }

    protected function uninstallCacheOverride()
    {
        $targetPath = $this->getInstalledOverridePath();

        if (file_exists($targetPath) && !unlink($targetPath)) {
            return false;
        }

        return $this->refreshClassIndex();
    }

    protected function getModuleOverridePath()
    {
        return _PS_MODULE_DIR_ . $this->name . '/override/classes/cache/CacheRedis.php';
    }

    protected function getInstalledOverridePath()
    {
        return _PS_ROOT_DIR_ . '/override/classes/cache/CacheRedis.php';
    }

    protected function refreshClassIndex()
    {
        if (method_exists('Tools', 'generateIndex')) {
            Tools::generateIndex();
        }

        foreach (glob(_PS_ROOT_DIR_ . '/var/cache/*/class_index.php') ?: array() as $classIndexFile) {
            @unlink($classIndexFile);
        }

        return true;
    }
}
