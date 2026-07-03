<?php

namespace Leantime\Plugins\__PLUGIN_STUDLY__\Services;

use Leantime\Domain\Plugins\Contracts\PluginInterface;

/**
 * __PLUGIN_STUDLY__ - Plugin lifecycle service.
 *
 * Instantiated by Leantime's plugin manager to run install/uninstall/enable/
 * disable hooks. The plugin manager resolves this class as
 * `Leantime\Plugins\__PLUGIN_STUDLY__\Services\__PLUGIN_STUDLY__` (named after
 * the plugin folder), so this file MUST be renamed to match the folder name.
 */
class __PLUGIN_STUDLY__ implements PluginInterface
{
    /**
     * install - Run on plugin installation (e.g. create database tables).
     */
    public function install(): bool
    {
        return true;
    }

    /**
     * uninstall - Run on plugin removal (e.g. drop database tables).
     */
    public function uninstall(): bool
    {
        return true;
    }

    /**
     * enable - Run when the plugin is enabled.
     */
    public function enable(): bool
    {
        return true;
    }

    /**
     * disable - Run when the plugin is disabled.
     */
    public function disable(): bool
    {
        return true;
    }
}
