<?php

/**
 * register.php - Lace plugin registration.
 *
 * Read and included early in the stack by the PluginManager. Uses the
 * Registration helper to hook into commonly used events (language files, menus).
 *
 * @see https://docs.leantime.io/development/plugin-development
 */

use Leantime\Core\Events\EventDispatcher;
use Leantime\Core\Language;
use Leantime\Domain\Plugins\Services\Registration;

$registration = app()->makeWith(Registration::class, ['pluginId' => 'Lace']);

// Register languages (merges en-US as the base translation set)
$registration->registerLanguageFiles(['en-US', 'pt-BR']);

// Merge the plugin language file matching the resolved UI language.
// Registration::registerLanguageFiles() only honors session('usersettings.language'),
// which stays unset for sessions bootstrapped on the login screen (Localization
// caches early without a user). Resolve like the core Language class instead
// (user setting -> company setting -> configured default) and merge on top of en-US.
EventDispatcher::add_event_listener(
    'leantime.core.middleware.loadplugins.handle.pluginsEvents',
    function (): void {
        $language = app()->make(Language::class);
        $current = $language->getCurrentLanguage();
        $file = __DIR__.'/Language/'.$current.'.ini';

        if ($current !== 'en-US' && file_exists($file)) {
            $translations = parse_ini_file($file, false, INI_SCANNER_RAW);
            if (is_array($translations)) {
                $language->mergeLanguageArray($translations);
            }
        }
    },
    6
);

// Add menu item
$registration->addMenuItem([
    'title' => 'lace.menu.title',
    'icon' => 'fa fa-diagram-project',
    'tooltip' => 'lace.menu.tooltip',
    'href' => '/lace/show',
], 'personal', [10]);
