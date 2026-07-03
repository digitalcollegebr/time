<?php

namespace Leantime\Plugins\Lace\Controllers;

use Leantime\Core\Controller\Controller;
use Leantime\Plugins\Lace\Services\Dashboard as DashboardService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Show - LACE dashboard controller.
 *
 * Reachable via `/lace/show`. Renders the decision-oriented LACE dashboard:
 * maturity KPIs, the three nuclei honeycombs (score gradient per objective)
 * and the attention radar of lowest scores.
 */
class Show extends Controller
{
    private DashboardService $dashboardService;

    public function init(DashboardService $dashboardService): void
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * get - Display the LACE dashboard for the dedicated LACE project.
     */
    public function get(): Response
    {
        $data = $this->dashboardService->getDashboardData();

        array_map([$this->tpl, 'assign'], array_keys($data), array_values($data));

        return $this->tpl->display('lace.show');
    }
}
