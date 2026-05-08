# Redis Cache Pro — PrestaShop 9

A PrestaShop 9 module that provides a Redis-backed `CacheRedis` implementation with a Back Office configuration screen for managing Redis connection settings.

**Version:** 1.2.0  
**Compatibility:** PrestaShop 1.7.0.0 – 9.x  
**PHP:** 8.1, 8.2, 8.3, 8.4  
**Redis client:** [Predis](https://github.com/predis/predis) 2.2.2 (bundled — no PHP extension required)

---

## Features

- Back Office configuration screen for Redis connection settings
- Unix socket support for lowest-latency local connections (recommended)
- TCP connection support as fallback
- Test connection button
- Flush Redis cache button
- Installs `CacheRedis` via PrestaShop's override mechanism
- Syncs `app/config/parameters.php` on enable/disable so PrestaShop 9 boots with the correct cache backend
- Tools panel shows live diagnostics: Predis available, override installed, `parameters.php` writable and synced

---

## Installation

1. Upload the module folder to `/modules/rediscache/`
2. Install via Back Office → Modules
3. Go to **Modules → Redis Cache Pro → Configure**
4. Enter your Redis connection details (socket path recommended, or IP + Port)
5. Enable caching and click **Save** — the module will write `parameters.php` automatically
6. Click **Test Redis** to confirm the connection

### Unix socket (recommended per audit Perf-4)

In `/etc/redis/redis.conf`:
```
unixsocket /var/run/redis/redis.sock
unixsocketperm 770
```

Add `www-data` to the `redis` group:
```bash
usermod -aG redis www-data
```

Set in the module config:  
**Unix socket path:** `/var/run/redis/redis.sock`  
Leave IP and Port blank.

---

## Configuration reference

| Field | Default | Notes |
|---|---|---|
| Enable caching | Off | Writes `PS_CACHE_ENABLED=1` and `PS_CACHING_SYSTEM=CacheRedis` |
| Unix socket path | *(empty)* | When set, IP and Port are ignored |
| IP address / domain | 127.0.0.1 | Used when no socket path is set |
| Port | 6379 | Used when no socket path is set |
| Authentication | *(empty)* | Redis `requirepass` value |
| Database | 0 | 0–15 |
| Lifetime (days) | *(empty)* | Leave empty for no TTL |

---

## Architecture

```
PrestaShop 9
  └── CacheRedis override  (installed to /override/classes/cache/)
        └── RCRedisCache   (module class — extends PS Cache)
              └── Predis\Client  (bundled vendor library)
                    └── Redis server  (TCP or Unix socket)
```

`parameters.php` is written by the module on save so the Symfony bootstrap can
resolve the cache backend before the database is available.

---

## Changelog

### 1.2.0
- **Fix:** `_exists()` always returned `true` due to integer `0 !== false` being `true` in PHP
- **Fix:** `_delete()` was building a regex pattern and passing it to Redis `KEYS` (which uses glob) — now uses non-blocking `SCAN`/`MATCH`
- **Fix:** `connect()` called `KEYS *` on every request (O(N) blocking) — replaced with a single `PING`
- **Fix:** Caching a `false` value was indistinguishable from a cache miss — values now wrapped in a `{"v":...}` sentinel
- **Fix:** `@json_encode` was silently swallowing serialisation errors
- **Fix:** `disableRedisCaching()` never reset `PS_CACHING_SYSTEM`, allowing accidental reactivation
- **Fix:** `uninstallCacheOverride()` ignored the `unlink()` return value
- **Fix:** `upgrade-1.0.2.php` called `require` on `parameters.php` without a file-existence check
- **Fix:** Back Office JS AJAX test did not URL-encode connection parameters — passwords with `&`, `=`, `#` would break
- **Fix:** Back Office JS null-reference crashes on pages where expected DOM elements don't exist
- **Feature:** Unix socket connection support (recommended by audit Perf-4 for ~10–15% lower cache latency)
- **Feature:** `parameters.php` is now synced on enable/disable so PrestaShop 9 activates Redis at Symfony bootstrap
- **Feature:** Tools panel now shows `parameters.php` writable and sync status

### 1.1.0
- Migrated settings from `app/config/parameters.php` to PrestaShop Configuration API
- Replaced generated `CacheRedis` class with packaged module override
- Removed DOM injection into the legacy Performance page

### 1.0.2
- Initial public release with `parameters.php`-based configuration

---

## Cache key blacklist

The following key prefixes are never stored in Redis (they remain in the default cache to protect session and transactional data):

`cart`, `cart_cart_rule`, `cart_product`, `connections`, `connections_source`, `connections_page`, `customer`, `customer_group`, `customized_data`, `guest`, `pagenotfound`, `page_viewed`, `employee`, `log`

---

## License

Valid for 1 website (or project) per purchased license.  
Copyright ETS Software Technology Co., Ltd. All rights reserved.
