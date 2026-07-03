<?php

namespace Leantime\Plugins\Lace\Controllers;

use Leantime\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Show - Main controller for the Lace plugin.
 *
 * Leantime uses the frontcontroller pattern and routes automatically based on
 * the URL structure. This controller is reachable via `/lace/show`.
 */
class Show extends Controller
{
    /**
     * get - Display the Lace plugin main page.
     */
    public function get(): Response
    {
        return $this->tpl->display('lace.show');
    }
}
