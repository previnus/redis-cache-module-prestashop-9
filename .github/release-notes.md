## What's changed

### Bug fixes
- `_exists()` always returned `true` due to integer `0 !== false` being `true` in PHP
- `_delete()` was building a regex pattern and passing it to Redis `KEYS` (which uses glob) — now uses non-blocking `SCAN`/`MATCH`
- `connect()` called `KEYS *` on every request (O(N) blocking) — replaced with a single `PING`
- Caching a `false` value was indistinguishable from a cache miss — values now wrapped in a `{"v":...}` sentinel
- `@json_encode` was silently swallowing serialisation errors
- `disableRedisCaching()` never reset `PS_CACHING_SYSTEM`, allowing accidental reactivation
- `uninstallCacheOverride()` ignored the `unlink()` return value
- `upgrade-1.0.2.php` called `require` on `parameters.php` without a file-existence check
- Back Office JS AJAX test did not URL-encode connection parameters — passwords with `&`, `=`, `#` would break
- Back Office JS null-reference crashes on pages where expected DOM elements don't exist

### New features
- Unix socket connection support (recommended for ~10–15% lower cache latency)
- `parameters.php` is now synced on enable/disable so PrestaShop 9 activates Redis at Symfony bootstrap
- Tools panel now shows `parameters.php` writable and sync status
- Upgrade script (`upgrade-1.2.0.php`) migrates existing installs automatically
