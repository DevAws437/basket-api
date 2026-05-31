<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchRecord;
use App\Services\ActionService;
use Illuminate\Http\Request;

class ActionController extends Controller
{
    public function __construct(
        private ActionService $actionService,
    ) {}

    public function record(Request $request, MatchRecord $match)
    {
        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
            'action_type' => 'required|string',
            'period_id' => 'nullable|exists:match_periods,id',
            'related_player_id' => 'nullable|exists:players,id',
        ]);

        try {
            $action = $this->actionService->recordAction(
                $match,
                $validated['player_id'],
                $validated['action_type'],
                $validated['period_id'] ?? null,
                $validated['related_player_id'] ?? null,
            );

            $response = [
                'action' => $action->load('player'),
                'team_score' => $match->fresh()->team_score,
                'opponent_score' => $match->opponent_score,
            ];

            if (!empty($action->force_foul_out)) {
                $response['force_substitution'] = true;
                $response['fouled_player_name'] = $action->fouled_player_name ?? '';
                $response['fouled_player_id'] = $action->player_id;
            }

            return response()->json($response);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل تسجيل الحدث'], 500);
        }
    }

    public function substitute(Request $request, MatchRecord $match)
    {
        $validated = $request->validate([
            'player_out_id' => 'required|exists:players,id',
            'player_in_id' => 'required|exists:players,id',
        ]);

        try {
            $actions = $this->actionService->substitute(
                $match,
                $validated['player_out_id'],
                $validated['player_in_id'],
            );

            $lineupService = app(\App\Services\LineupService::class);

            return response()->json([
                'actions' => $actions,
                'active_players' => $lineupService->getActivePlayers($match),
                'bench_players' => $lineupService->getBenchPlayers($match),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل التبديل'], 500);
        }
    }

    public function undo(MatchRecord $match)
    {
        try {
            $undoneAction = $this->actionService->undoLastAction($match);

            if (!$undoneAction) {
                return response()->json(['message' => 'لا يوجد إجراءات للتراجع عنها'], 404);
            }

            $lineupService = app(\App\Services\LineupService::class);

            return response()->json([
                'undone_action' => $undoneAction,
                'team_score' => $match->fresh()->team_score,
                'active_players' => $lineupService->getActivePlayers($match),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'فشل التراجع عن الحدث'], 500);
        }
    }
}
