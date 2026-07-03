<?php

namespace Leantime\Plugins\Lace\Services;

use Leantime\Domain\Goalcanvas\Repositories\Goalcanvas as GoalcanvasRepository;
use Leantime\Domain\Goalcanvas\Services\Goalcanvas as GoalcanvasService;
use Leantime\Domain\Projects\Services\Projects as ProjectsService;

/**
 * Dashboard - LACE methodology dashboard orchestration.
 *
 * Ensures a dedicated "LACE" project exists holding one goal board whose goals
 * (the honeycomb cells / "colmeias") represent the three LACE strategy nuclei.
 * Reads each goal's completion percentage (goalProgress, computed by the core
 * Goalcanvas service) to drive the red->green gradient in the dashboard view,
 * plus the decision layer (overall maturity, per-nucleus averages) and the
 * attention radar (lowest scores).
 */
class Dashboard
{
    /** Name of the dedicated project that holds the LACE objectives. */
    public const PROJECT_NAME = 'LACE';

    /** Title of the goal board created inside the LACE project. */
    public const BOARD_TITLE = 'LACE - Estratégia de IA';

    /** Score below which a cell is flagged as critical (redundant "!" encoding). */
    public const CRITICAL_THRESHOLD = 25;

    /**
     * The honeycomb structure: each nucleus with its honeycomb rows.
     * Every objective becomes a Leantime goal seeded at 0% (start 0 / current 0 / end 100).
     *
     * @var array<int, array{key:string, title:string, subtitle:string, rows:array<int, string[]>}>
     */
    public const NUCLEI = [
        [
            'key' => 'estrategia',
            'title' => 'Estratégia de IA',
            'subtitle' => 'definição de objetivos',
            'rows' => [
                ['Alinhamento'],
                ['Impulsionadores', 'Riscos'],
                ['Visão'],
                ['Valor', 'Adoção'],
            ],
        ],
        [
            'key' => 'portfolio',
            'title' => 'Portfólio de IA',
            'subtitle' => 'identificação e realização de valor',
            'rows' => [
                ['Ideação / priorização', 'Comprar ou construir'],
                ['Casos de uso'],
                ['Gestão de valor / custos', 'Gestão de mudanças'],
            ],
        ],
        [
            'key' => 'modelo',
            'title' => 'Modelo operacional de IA',
            'subtitle' => 'roteiro e maturidade',
            'rows' => [
                ['Governança', 'Engenharia'],
                ['Dados'],
                ['Letramento', 'Tecnologia'],
                ['Organização'],
            ],
        ],
    ];

    public function __construct(
        private ProjectsService $projectsService,
        private GoalcanvasService $goalService,
        private GoalcanvasRepository $goalRepository,
    ) {}

    /**
     * Flat list of every objective (colmeia) name across all nuclei/rows.
     *
     * @return string[]
     */
    public static function allObjectiveNames(): array
    {
        $names = [];
        foreach (self::NUCLEI as $nucleus) {
            foreach ($nucleus['rows'] as $row) {
                foreach ($row as $name) {
                    $names[] = $name;
                }
            }
        }

        return $names;
    }

    /**
     * Sequential red->green colour for a 0-100 score. Hue runs 6° (red) to 140°
     * (green); lightness dips mid-scale so white text stays readable on yellows.
     */
    public static function scoreColor(int $score): string
    {
        $score = max(0, min(100, $score));
        $hue = (int) round(6 + ($score / 100) * 134);
        $light = (int) round(49 - 9 * sin(M_PI * $score / 100));

        return "hsl({$hue}, 52%, {$light}%)";
    }

    /**
     * Build everything the dashboard view needs. Creates the project/board/goals
     * on first run so opening the dashboard is enough to bootstrap it all.
     *
     * @return array{
     *   projectName:string, boardId:int, totalGoals:int, overall:int,
     *   overallColor:string, ringOffset:float, lastUpdated:?string,
     *   nuclei:array<int, array{key:string, title:string, subtitle:string, avg:int, avgColor:string,
     *     rows:array<int, array<int, array{name:string, score:int, color:string, critical:bool}>>}>,
     *   worst:array<int, array{name:string, nucleus:string, score:int, color:string}>
     * }
     */
    public function getDashboardData(): array
    {
        $projectId = $this->ensureProject();
        $boardId = $this->ensureBoard($projectId);
        $this->ensureGoals($boardId);

        // Map existing goals (by title) to their computed progress percentage.
        $scoresByName = [];
        $lastModified = null;
        foreach ($this->goalService->getCanvasItemsById($boardId) ?: [] as $goal) {
            $scoresByName[trim($goal['title'])] = (int) round($goal['goalProgress'] ?? 0);
            if (! empty($goal['modified']) && ($lastModified === null || $goal['modified'] > $lastModified)) {
                $lastModified = $goal['modified'];
            }
        }

        $nuclei = [];
        $allGoals = [];
        foreach (self::NUCLEI as $nucleus) {
            $rows = [];
            $sum = 0;
            $count = 0;
            foreach ($nucleus['rows'] as $row) {
                $cells = [];
                foreach ($row as $name) {
                    $score = $scoresByName[$name] ?? 0;
                    $sum += $score;
                    $count++;
                    $cells[] = [
                        'name' => $name,
                        'score' => $score,
                        'color' => self::scoreColor($score),
                        'critical' => $score < self::CRITICAL_THRESHOLD,
                    ];
                    $allGoals[] = ['name' => $name, 'nucleus' => $nucleus['title'], 'score' => $score];
                }
                $rows[] = $cells;
            }
            $avg = $count > 0 ? (int) round($sum / $count) : 0;
            $nuclei[] = [
                'key' => $nucleus['key'],
                'title' => $nucleus['title'],
                'subtitle' => $nucleus['subtitle'],
                'avg' => $avg,
                'avgColor' => self::scoreColor($avg),
                'rows' => $rows,
            ];
        }

        $overall = count($allGoals) > 0
            ? (int) round(array_sum(array_column($allGoals, 'score')) / count($allGoals))
            : 0;

        usort($allGoals, fn ($a, $b) => $a['score'] <=> $b['score']);
        $worst = array_map(fn ($g) => $g + ['color' => self::scoreColor($g['score'])], array_slice($allGoals, 0, 3));

        // Ring circumference for r=15.5 in the 36x36 viewBox.
        $circumference = 2 * M_PI * 15.5;

        return [
            'projectName' => $this->projectsService->getProjectName($projectId) ?: self::PROJECT_NAME,
            'boardId' => $boardId,
            'totalGoals' => count($allGoals),
            'overall' => $overall,
            'overallColor' => self::scoreColor($overall),
            'ringOffset' => round($circumference * (1 - $overall / 100), 1),
            'lastUpdated' => $lastModified ? date('d/m/Y', strtotime($lastModified)) : null,
            'nuclei' => $nuclei,
            'worst' => $worst,
        ];
    }

    /**
     * Find the dedicated LACE project, creating it (client-less, visible to all) if missing.
     */
    private function ensureProject(): int
    {
        $existingId = $this->projectsService->getProjectIdbyName(
            $this->projectsService->getAllProjects(),
            self::PROJECT_NAME
        );

        if ($existingId !== false) {
            return (int) $existingId;
        }

        return (int) $this->projectsService->addProject([
            'name' => self::PROJECT_NAME,
            'details' => 'Projeto dedicado à metodologia LACE (dashboard de Estratégia de IA).',
            'clientId' => 0,
            'psettings' => 'all',
        ]);
    }

    /**
     * Find the LACE goal board within the project, creating it if missing.
     */
    private function ensureBoard(int $projectId): int
    {
        $boards = $this->goalRepository->getAllCanvas($projectId) ?: [];
        foreach ($boards as $board) {
            if (trim($board['title']) === self::BOARD_TITLE) {
                return (int) $board['id'];
            }
        }

        return (int) $this->goalService->createGoalboard([
            'title' => self::BOARD_TITLE,
            'author' => (int) session('userdata.id'),
            'projectId' => $projectId,
        ]);
    }

    /**
     * Create any missing objective (colmeia) goals on the board, each seeded at 0%
     * (startValue 0, currentValue 0, endValue 100). Idempotent: existing titles are skipped.
     */
    private function ensureGoals(int $boardId): void
    {
        $existing = [];
        foreach ($this->goalService->getCanvasItemsById($boardId) ?: [] as $goal) {
            $existing[trim($goal['title'])] = true;
        }

        $author = (int) session('userdata.id');

        foreach (self::allObjectiveNames() as $name) {
            if (isset($existing[$name])) {
                continue;
            }

            $this->goalService->createGoal([
                'canvasId' => $boardId,
                'box' => 'goal',
                'title' => $name,
                'author' => $author,
                'status' => '',
                'setting' => '',
                'metricType' => '',
                'startValue' => 0,
                'currentValue' => 0,
                'endValue' => 100,
            ]);
        }
    }
}
