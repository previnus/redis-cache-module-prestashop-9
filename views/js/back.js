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

document.addEventListener('DOMContentLoaded', () => {

    const caching_caching_system = document.getElementById("caching_caching_system");
    const redis_cache_option = document.querySelector(".redis-cache-option");
    const redis_cache_form = document.getElementById("redis_cache");
    const form_wrapper = document.getElementById("caching");
    const redisCacheBuild = document.getElementById('redis-cache-build');

    if (caching_caching_system && redis_cache_option && redis_cache_form && form_wrapper) {
        caching_caching_system.appendChild(redis_cache_option);
        form_wrapper.parentElement.appendChild(redis_cache_form);
        if (redisCacheBuild) {
            redisCacheBuild.remove();
        }

        const caching_system = document.querySelectorAll("input[name='caching[caching_system]']");
        caching_system.forEach((radio) => {
            if (typeof _PS_CACHING_SYSTEM_ !== typeof undefined && radio.value == _PS_CACHING_SYSTEM_ && _PS_CACHING_SYSTEM_ == 'CacheRedis') {
                radio.click();
            }
            radio.addEventListener("change", function () {
                if (radio.value == "CacheRedis") {
                    redis_cache_form.style.display = "block";
                } else {
                    redis_cache_form.style.display = "none";
                }
            });
        });

        const new_server_btn = document.getElementById('new-server-btn');
        const servers_list = document.getElementById('servers-list');

        const use_cache = document.querySelectorAll("input[name='caching[use_cache]']");
        use_cache.forEach((radio) => {
            radio.addEventListener("change", function () {
                let caching_system_new = document.querySelector("input[name='caching[caching_system]']:checked");
                if (radio.value == 1 && caching_system_new && caching_system_new.value == 'CacheRedis') {
                    redis_cache_form.style.display = "block";
                    if (new_server_btn) new_server_btn.style.display = "none";
                    if (servers_list) servers_list.style.display = "none";
                } else {
                    redis_cache_form.style.display = "none";
                }
            });
        });

        const use_cache_checked = document.querySelector("input[name='caching[use_cache]']:checked");
        const caching_system_checked = document.querySelector("input[name='caching[caching_system]']:checked");

        if (typeof _PS_CACHING_SYSTEM_ !== typeof undefined && caching_system_checked != null && caching_system_checked.value == _PS_CACHING_SYSTEM_ && _PS_CACHING_SYSTEM_ == 'CacheRedis' && use_cache_checked != null && use_cache_checked.value == 1) {
            redis_cache_form.style.display = "block";
        }
    } else if (redisCacheBuild) {
        redisCacheBuild.remove();
    }

    const flushBtn = document.getElementById('flush_all_redis_cache');
    if (flushBtn !== null) {
        flushBtn.addEventListener('click', function () {
            flushAllCacheRedis(this, true);
        });
    }

    const clearCacheBtn = document.getElementById('page-header-desc-configuration-clear_cache');
    if (clearCacheBtn !== null) {
        clearCacheBtn.addEventListener('click', function (event) {
            event.preventDefault();
            flushAllCacheRedis(this, false);
        });
    }

    function flushAllCacheRedis(target, showMessage) {
        if (typeof REDIS_CACHE_REQUEST_URL === typeof undefined) {
            return;
        }
        target.classList.add('loadingb');
        fetch(`${REDIS_CACHE_REQUEST_URL}&flush_redis_cache=1`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        }).then(response => {
            target.classList.remove('loadingb');
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        }).then(data => {
            target.classList.remove('loading');
            if (data.ok && showMessage) {
                showSuccessMessage(REDIS_CACHE_TRANSLATE_1);
            }
            if (!showMessage) {
                window.location.href = target.getAttribute('href');
            }
        }).catch(error => {
            target.classList.remove('loadingb');
            console.error('There was a problem with the request:', error);
        });
    }

    const testRedisBtn = document.getElementById('test_redis');
    if (testRedisBtn !== null) {
        testRedisBtn.addEventListener('click', function () {
            if (typeof REDIS_CACHE_REQUEST_URL === typeof undefined) {
                return;
            }
            this.classList.add('loadingb');

            const ip = document.getElementById('redis_cache_ip').value;
            const port = document.getElementById('redis_cache_port').value;
            const password = document.getElementById('redis_cache_password').value;
            const database = document.getElementById('redis_cache_database').value;

            fetch(`${REDIS_CACHE_REQUEST_URL}&test_redis_cache=1&ip=${encodeURIComponent(ip)}&port=${encodeURIComponent(port)}&password=${encodeURIComponent(password)}&database=${encodeURIComponent(database)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            }).then(response => {
                this.classList.remove('loadingb');
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            }).then(data => {
                this.classList.remove('loading');
                if (data.ok === 'pong') {
                    showSuccessMessage(REDIS_CACHE_TRANSLATE_2);
                } else if (data.ok === false) {
                    showErrorMessage(REDIS_CACHE_TRANSLATE_3);
                } else {
                    showErrorMessage(data.ok);
                }
            }).catch(error => {
                this.classList.remove('loadingb');
                console.error(error);
            });
        });
    }
});
