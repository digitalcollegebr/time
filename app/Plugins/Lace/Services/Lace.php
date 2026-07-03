<?php

namespace Leantime\Plugins\Lace\Services;

use Leantime\Domain\Plugins\Contracts\PluginInterface;

/**
 * Lace - Plugin lifecycle service.
 *
 * Instantiated by Leantime's plugin manager to run install/uninstall/enable/
 * disable hooks. The Lace plugin does not create any database tables yet, so
 * each hook simply reports success.
 */
class Lace implements PluginInterface
{
    /**
     * install - Run on plugin installation.
     */
    public function install(): bool
    {
        // No database tables required for this plugin yet.
        return true;
    }

    /**
     * uninstall - Run on plugin removal.
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
