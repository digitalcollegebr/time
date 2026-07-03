<?php

/**
 * register.php - __PLUGIN_STUDLY__ plugin registration.
 *
 * Read and included early in the stack by the PluginManager. Uses the
 * Registration helper to hook into commonly used events (language files, menus).
 *
 * @see https://docs.leantime.io/development/plugin-development
 */

use Leantime\Domain\Plugins\Services\Registration;

$registration = app()->makeWith(Registration::class, ['pluginId' => '__PLUGIN_STUDLY__']);

// Register languages
$registration->registerLanguageFiles(['en-US']);

// Add menu item
$registration->addMenuItem([
    'title' => '__PLUGIN_LOWER__.menu.title',
    'icon' => 'fa fa-puzzle-piece',
    'tooltip' => '__PLUGIN_LOWER__.menu.tooltip',
    'href' => '/__PLUGIN_LOWER__/show',
], 'personal', [10]);
