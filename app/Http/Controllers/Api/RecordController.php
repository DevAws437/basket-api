<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatchResource;
use App\Models\MatchRecord;
use App\Models\PlayerAction;
use App\Services\ExportService;
use App\Services\StatisticsService;
use App\Services\MVPCalculator;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService,
        private MVPCalculator $mvpCalculator,
        private ExportService $exportService,
    ) {}

    public function index(Request $request)
    {
        try {
            $query = MatchRecord::completed()->with('team');

            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            }

            if ($request->filled('team')) {
                $query->whereHas('team', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->team}%");
                });
            }

            if ($request->filled('score_min')) {
                $query->where('team_score', '>=', $request->score_min);
            }

            if ($request->filled('score_max')) {
                $query->where('team_score', '<=', $request->score_max);
            }

            $matches = $query->orderBy('created_at', 'desc')->get();

            return MatchResource::collection($matches);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل جلب السجلات'], 500);
        }
    }

    public function show(MatchRecord $match)
    {
        try {
            if ($match->status !== 'completed') {
                return response()->json(['error' => 'المباراة لم تنته بعد'], 400);
            }

            $match->load('team', 'periods');

            $actions = PlayerAction::where('match_id', $match->id)
                ->where('is_undo', false)
                ->whereIn('action_type', ['shot_2pt_made', 'shot_3pt_made', 'ft_made'])
                ->with('player')
                ->orderBy('id')
                ->get();

            $runningTeam = 0;
            $chartData = [];
            foreach ($actions as $a) {
                $runningTeam += $a->points;
                $chartData[] = [
                    'elapsed' => $a->action_timestamp,
                    'points' => $a->points,
                    'team_score' => $runningTeam,
                    'action_type' => $a->action_type,
                    'player_name' => $a->player?->full_name ?? $a->player?->first_name ?? 'Unknown',
                ];
            }

            return response()->json([
                'match' => new MatchResource($match),
                'team_stats' => $this->statisticsService->getTeamStats($match),
                'player_stats' => $this->statisticsService->getAllPlayerStats($match),
                'mvp' => $this->mvpCalculator->calculate($match),
                'flow' => $this->statisticsService->getFlowData($match),
                'chart_data' => $chartData,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل جلب تفاصيل المباراة'], 500);
        }
    }

    public function export(MatchRecord $match)
    {
        try {
            if ($match->status !== 'completed') {
                return response()->json(['error' => 'المباراة لم تنته بعد'], 400);
            }

            return $this->exportService->downloadMatch($match);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل تصدير الملف'], 500);
        }
    }
}
