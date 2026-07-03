<?php

/**
 * register.php - Lace plugin registration.
 *
 * Read and included early in the stack by the PluginManager. Uses the
 * Registration helper to hook into commonly used events (language files, menus).
 *
 * @see https://docs.leantime.io/development/plugin-development
 */

use Leantime\Domain\Plugins\Services\Registration;

$registration = app()->makeWith(Registration::class, ['pluginId' => 'Lace']);

// Register languages
$registration->registerLanguageFiles(['en-US']);

// Add menu item
$registration->addMenuItem([
    'title' => 'lace.menu.title',
    'icon' => 'fa fa-puzzle-piece',
    'tooltip' => 'lace.menu.tooltip',
    'href' => '/lace/show',
], 'personal', [10]);
