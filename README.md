# Redis Cache Module (PrestaShop 9)

Redis Cache Module is a PrestaShop module that provides a Redis-backed `CacheRedis` implementation and a Back Office configuration screen for managing Redis connection settings.

The current codebase is geared for **PrestaShop 9**. The compatibility work in this repository removes the older pattern of writing directly to `app/config/parameters.php` and generating cache classes inside core directories, and instead uses PrestaShop configuration storage plus an override-based `CacheRedis` provider lifecycle.

## What it does

- stores Redis connection settings in PrestaShop `Configuration`
- exposes a module configuration page in Back Office
- provides tools to test the Redis connection
- provides a tool to flush the configured Redis cache database
- installs a `CacheRedis` override from the module package instead of generating one into core paths

## PrestaShop 9 orientation

This repository is maintained with a **PrestaShop 9-oriented implementation approach**:

- module settings are stored through the PrestaShop configuration API
- legacy direct writes to `app/config/parameters.php` are no longer used
- the cache class is packaged as a module override
- brittle DOM injection into the Performance page has been removed in favor of a standard module configuration screen

## Main files

- `rediscache.php` — main module class and configuration screen
- `classes/RCRedisCache.php` — Redis-backed cache implementation
- `override/classes/cache/CacheRedis.php` — packaged override installed into PrestaShop override path
- `upgrade/upgrade-1.1.0.php` — legacy settings migration and compatibility upgrade logic

## Validation performed in this repository

- PHP syntax lint passed for the changed module files
- a stubbed local runtime check verified that the module configuration page renders

## Important note

Although the code is geared for PrestaShop 9, you should still validate it in a real PrestaShop 9 installation before production use, especially for:

- install / uninstall flow
- upgrade from an older module version
- override installation and removal
- cache clearing behavior from Back Office
- real Redis connection and persistence behavior

## Repository purpose

This repository contains the module source and the PrestaShop 9 compatibility adjustments.
