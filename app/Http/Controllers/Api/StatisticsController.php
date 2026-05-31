<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerStatsResource;
use App\Models\MatchRecord;
use App\Services\StatisticsService;
use App\Services\MVPCalculator;

class StatisticsController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService,
        private MVPCalculator $mvpCalculator,
    ) {}

    public function teamStats(MatchRecord $match)
    {
        try {
            $stats = $this->statisticsService->getTeamStats($match);
            return response()->json($stats);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل جلب إحصائيات الفريق'], 500);
        }
    }

    public function playerStats(MatchRecord $match)
    {
        try {
            $stats = $this->statisticsService->getAllPlayerStats($match);
            return PlayerStatsResource::collection($stats);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل جلب إحصائيات اللاعبين'], 500);
        }
    }

    public function playerDetail(MatchRecord $match, int $playerId)
    {
        try {
            $stats = $this->statisticsService->getPlayerStats($match, $playerId);
            return response()->json($stats);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'اللاعب غير موجود'], 404);
        }
    }

    public function flow(MatchRecord $match)
    {
        try {
            $flow = $this->statisticsService->getFlowData($match);
            return response()->json($flow);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل جلب تدفق المباراة'], 500);
        }
    }

    public function mvp(MatchRecord $match)
    {
        try {
            $mvp = $this->mvpCalculator->calculate($match);

            if (!$mvp) {
                return response()->json(['message' => 'لا توجد بيانات كافية'], 404);
            }

            return response()->json($mvp);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل حساب MVP'], 500);
        }
    }
}
