<?php

namespace Leantime\Plugins\__PLUGIN_STUDLY__\Controllers;

use Leantime\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Show - Main controller for the __PLUGIN_STUDLY__ plugin.
 *
 * Leantime uses the frontcontroller pattern and routes automatically based on
 * the URL structure. This controller is reachable via `/__PLUGIN_LOWER__/show`.
 */
class Show extends Controller
{
    /**
     * get - Display the __PLUGIN_STUDLY__ plugin main page.
     */
    public function get(): Response
    {
        // View namespace is the lowercase plugin folder name -> Templates/show.blade.php
        return $this->tpl->display('__PLUGIN_LOWER__.show');
    }
}
