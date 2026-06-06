<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatchResource;
use App\Models\MatchRecord;
use App\Services\MatchService;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(
        private MatchService $matchService,
    ) {}

    public function index(Request $request)
    {
        try {
            $query = MatchRecord::with('team', 'periods');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            }
            if ($request->filled('team_id')) {
                $query->where('team_id', $request->team_id);
            }

            $matches = $query->orderBy('id', 'desc')
                ->paginate($request->per_page ?? 20);

            return MatchResource::collection($matches);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل تحميل المباريات'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:official,training',
                'team_id' => 'required|exists:teams,id',
                'opponent_name' => 'required|string|max:100',
                'lineup' => 'required|array|size:5',
                'lineup.*' => 'exists:players,id',
            ]);

            $match = $this->matchService->createMatch($validated);
            $match->load('team', 'periods');

            $lineupService = app(\App\Services\LineupService::class);
            $lineupService->createInitialLineup($match, $validated['lineup']);

            $allPlayerIds = $match->team->players->pluck('id')->toArray();
            $lineupService->addBenchPlayers($match, $allPlayerIds, $validated['lineup']);

            return new MatchResource($match->load('team', 'periods', 'activePlayers'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل إنشاء المباراة'], 500);
        }
    }

    public function show(MatchRecord $match)
    {
        try {
            $match->load('team', 'periods', 'activePlayers.player');
            return new MatchResource($match);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'المباراة غير موجودة'], 404);
        }
    }

    public function start(MatchRecord $match)
    {
        try {
            $this->matchService->startMatch($match);
            return new MatchResource($match->fresh()->load('team', 'periods'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل بدء المباراة'], 500);
        }
    }

    public function togglePause(MatchRecord $match)
    {
        try {
            $paused = $this->matchService->togglePause($match);
            return response()->json([
                'is_paused' => $paused,
                'match' => new MatchResource($match->fresh()->load('team', 'periods')),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل إيقاف/استئناف المباراة'], 500);
        }
    }

    public function updateOpponentScore(Request $request, MatchRecord $match)
    {
        try {
            $validated = $request->validate([
                'score' => 'required|integer|min:0',
            ]);

            $match->update(['opponent_score' => $validated['score']]);

            return response()->json([
                'opponent_score' => $match->fresh()->opponent_score,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل تحديث نقاط الخصم'], 500);
        }
    }

    public function updateTeamScore(Request $request, MatchRecord $match)
    {
        try {
            $validated = $request->validate([
                'score' => 'required|integer|min:0',
            ]);

            $match->update(['team_score' => $validated['score']]);

            return response()->json([
                'team_score' => $match->fresh()->team_score,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل تحديث نقاط الفريق'], 500);
        }
    }

    public function endPeriod(MatchRecord $match)
    {
        try {
            $result = $this->matchService->endPeriod($match);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل إنهاء الربع'], 500);
        }
    }

    public function addOvertime(MatchRecord $match)
    {
        try {
            $period = $this->matchService->addOvertime($match);
            return response()->json([
                'period' => $period,
                'current_period' => $match->fresh()->current_period,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل إضافة شوط إضافي'], 500);
        }
    }

    public function endMatch(MatchRecord $match)
    {
        try {
            $result = $this->matchService->endMatch($match);
            return response()->json([
                'match' => new MatchResource($result['match']->load('team', 'periods')),
                'result' => $result['result'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل إنهاء المباراة'], 500);
        }
    }

    public function destroy(MatchRecord $match)
    {
        try {
            $match->actions()->delete();
            $match->periods()->delete();
            $match->lineups()->delete();
            $match->delete();

            return response()->json(['message' => 'تم حذف المباراة']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل حذف المباراة'], 500);
        }
    }
}
