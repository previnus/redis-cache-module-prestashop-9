{*
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
*}

<div id="redis-cache-build" style="display: none !important;">
    <div class="form-check form-check-radio form-radio redis-cache-option">
        <label class="form-check-label">
            <input type="radio" id="caching_caching_system_redis" name="caching[caching_system]"{if !$redis_loaded} disabled="disabled"{/if} class="form-check-input" value="CacheRedis">
            <i class="form-check-round"></i>{l s='Redis cache' mod='rediscache'}{if !$redis_loaded}&nbsp;({l s='you must install the' mod='rediscache'}<a href="https://pecl.php.net/package/redis" class="ml-1" target="_blank">{l s='Redis extension' mod='rediscache'}</a>){/if}
        </label>
    </div>
    <div id="redis_cache" style="display: none">
        <div>
            <div class="form-group row ">
                <label class="form-control-label required" for="redis_cache_ip"><span class="text-danger">*</span>{l s='Enter your IP address or your domain' mod='rediscache'}</label>
                <div class="col-sm-4">
                    <input type="text" id="redis_cache_ip" name="redis_cache_ip" aria-label="redis_cache_ip input" class="form-control" value="{$REDIS_CACHE_IP|escape:'html':'UTF-8'}">
                </div>
            </div>
            <div class="form-group row ">
                <label class="form-control-label required" for="redis_cache_port"><span class="text-danger">*</span>{l s='Port' mod='rediscache'}</label>
                <div class="col-sm-4">
                    <input type="text" id="redis_cache_port" name="redis_cache_port" aria-label="redis_cache_port input" class="form-control" value="{$REDIS_CACHE_PORT|escape:'html':'UTF-8'}">
                </div>
            </div>
            <div class="form-group row ">
                <label class="form-control-label required" for="redis_cache_password">{l s='Authentication' mod='rediscache'}</label>
                <div class="col-sm-4">
                    <input type="password" id="redis_cache_password" name="redis_cache_password" aria-label="redis_cache_password input" class="form-control" value="{$REDIS_CACHE_PASSWORD|escape:'html':'UTF-8'}">
                    <small id="redis_cache_password_help" class="form-text">{l s='If applicable, enter the auth key.' mod='rediscache'}</small>
                </div>
            </div>
            <div class="form-group row ">
                <label class="form-control-label required" for="redis_cache_database">{l s='Database' mod='rediscache'}</label>
                <div class="col-sm-4">
                    <input type="text" id="redis_cache_database" name="redis_cache_database" aria-label="redis_cache_database input" class="form-control" value="{$REDIS_CACHE_DATABASE|escape:'html':'UTF-8'}">
                    <small id="redis_cache_database_help" class="form-text">{l s='Enter the database number 0-15.' mod='rediscache'}</small>
                </div>
            </div>
            <div class="form-group row">
                <label class="form-control-label" for="redis_cache_life_time">{l s='Lifetime' mod='rediscache'}</label>
                <div class="input-group col-sm-4">
                    <input type="text" id="redis_cache_life_time" name="redis_cache_life_time" aria-label="redis_cache_life_time input" class="form-control" value="{$REDIS_CACHE_LIFE_TIME|escape:'html':'UTF-8'}">
                    <div class="input-group-append">
                        <span class="input-group-text js-countable-text">{l s='days' mod='rediscache'}</span>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="form-group row">
                <label class="form-control-label">&nbsp;</label>
                <div class="col-sm-4 test_redis_form_btn">
                    <span id="test_redis" class="btn btn-primary test-redis">
                        {l s='Test Redis' mod='rediscache'}
                    </span>
                    {if $PS_CACHE_ENABLED && $PS_CACHING_SYSTEM|trim=='CacheRedis'}
                        <div class="flush_all_redis_cache_on">
                            <span id="flush_all_redis_cache" class="btn flush-redis-cache">
                                <i class="material-icons">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="22" viewBox="0 -960 960 960" width="22"><path d="M304.615-160q-26.846 0-45.731-18.884Q240-197.769 240-224.615V-720h-40v-40h160v-30.77h240V-760h160v40h-40v495.385Q720-197 701.5-178.5 683-160 655.385-160h-350.77ZM680-720H280v495.385q0 10.769 6.923 17.692T304.615-200h350.77q9.23 0 16.923-7.692Q680-215.385 680-224.615V-720ZM392.307-280h40.001v-360h-40.001v360Zm135.385 0h40.001v-360h-40.001v360ZM280-720v520-520Z"/></svg>
                                </i>&nbsp;{l s='Flush Redis cache' mod='rediscache'}
                            </span>
                        </div>
                    {/if}
                </div>

            </div>

        </div>
    </div>
</div>