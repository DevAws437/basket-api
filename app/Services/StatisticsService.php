<?php

namespace App\Services;

use App\Enums\ActionType;
use App\Models\MatchRecord;
use App\Models\PlayerAction;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    public function getTeamStats(MatchRecord $match): array
    {
        $actions = PlayerAction::where('match_id', $match->id)
            ->where('is_undo', false)
            ->get();

        $twoPtMade = $actions->where('action_type', ActionType::Shot2PtMade->value)->count();
        $twoPtMissed = $actions->where('action_type', ActionType::Shot2PtMissed->value)->count();
        $threePtMade = $actions->where('action_type', ActionType::Shot3PtMade->value)->count();
        $threePtMissed = $actions->where('action_type', ActionType::Shot3PtMissed->value)->count();
        $ftMade = $actions->where('action_type', ActionType::FtMade->value)->count();
        $ftMissed = $actions->where('action_type', ActionType::FtMissed->value)->count();

        $fieldGoalsMade = $twoPtMade + $threePtMade;
        $fieldGoalsAttempted = $twoPtMade + $twoPtMissed + $threePtMade + $threePtMissed;
        $twoPtAttempted = $twoPtMade + $twoPtMissed;
        $threePtAttempted = $threePtMade + $threePtMissed;
        $ftAttempted = $ftMade + $ftMissed;

        return [
            'points' => $match->team_score,
            'opponent_score' => $match->opponent_score,
            'field_goals' => $this->formatStat($fieldGoalsMade, $fieldGoalsAttempted),
            'two_point' => $this->formatStat($twoPtMade, $twoPtAttempted),
            'three_point' => $this->formatStat($threePtMade, $threePtAttempted),
            'free_throws' => $this->formatStat($ftMade, $ftAttempted),
            'assists' => $actions->where('action_type', ActionType::Assist->value)->count(),
            'rebounds' => $actions->where('action_type', ActionType::Rebound->value)->count(),
            'steals' => $actions->where('action_type', ActionType::Steal->value)->count(),
            'turnovers' => $actions->where('action_type', ActionType::Turnover->value)->count(),
            'fouls' => $actions->where('action_type', ActionType::Foul->value)->count(),
        ];
    }

    public function getAllPlayerStats(MatchRecord $match): array
    {
        $lineups = $match->lineups()->with('player')->get();
        $stats = [];

        foreach ($lineups as $lineup) {
            $stats[] = $this->getPlayerStats($match, $lineup->player_id);
        }

        return $stats;
    }

    public function getPlayerStats(MatchRecord $match, int $playerId): array
    {
        $actions = PlayerAction::where('match_id', $match->id)
            ->where('player_id', $playerId)
            ->where('is_undo', false)
            ->get();

        $player = \App\Models\Player::find($playerId);
        $lineup = \App\Models\Lineup::where('match_id', $match->id)
            ->where('player_id', $playerId)
            ->first();

        $twoPtMade = $actions->where('action_type', ActionType::Shot2PtMade->value)->count();
        $twoPtMissed = $actions->where('action_type', ActionType::Shot2PtMissed->value)->count();
        $threePtMade = $actions->where('action_type', ActionType::Shot3PtMade->value)->count();
        $threePtMissed = $actions->where('action_type', ActionType::Shot3PtMissed->value)->count();
        $ftMade = $actions->where('action_type', ActionType::FtMade->value)->count();
        $ftMissed = $actions->where('action_type', ActionType::FtMissed->value)->count();

        $fieldGoalsMade = $twoPtMade + $threePtMade;
        $fieldGoalsAttempted = $twoPtMade + $twoPtMissed + $threePtMade + $threePtMissed;

        $points = ($twoPtMade * 2) + ($threePtMade * 3) + $ftMade;
        $rebounds = $actions->where('action_type', ActionType::Rebound->value)->count();
        $assists = $actions->where('action_type', ActionType::Assist->value)->count();
        $steals = $actions->where('action_type', ActionType::Steal->value)->count();
        $turnovers = $actions->where('action_type', ActionType::Turnover->value)->count();
        $fouls = $lineup ? $lineup->fouls : 0;

        $fgMissed = $twoPtMissed + $threePtMissed;
        $efficiency = $points + $rebounds + $assists + $steals - $fgMissed - $ftMissed - $turnovers;

        return [
            'player_id' => $playerId,
            'jersey_number' => $player?->jersey_number,
            'player_name' => $player ? $player->full_name : 'Unknown',
            'position' => $player?->position,
            'points' => $points,
            'field_goals' => $this->formatStat($fieldGoalsMade, $fieldGoalsAttempted),
            'two_point' => $this->formatStat($twoPtMade, $twoPtMade + $twoPtMissed),
            'three_point' => $this->formatStat($threePtMade, $threePtMade + $threePtMissed),
            'free_throws' => $this->formatStat($ftMade, $ftMade + $ftMissed),
            'rebounds' => $rebounds,
            'assists' => $assists,
            'steals' => $steals,
            'turnovers' => $turnovers,
            'fouls' => $fouls,
            'efficiency' => $efficiency,
        ];
    }

    public function getFlowData(MatchRecord $match): array
    {
        $periods = $match->periods()->orderBy('period_number')->get();
        $flow = [];

        foreach ($periods as $period) {
            $points = PlayerAction::where('match_id', $match->id)
                ->where('period_id', $period->id)
                ->where('is_undo', false)
                ->whereIn('action_type', [
                    ActionType::Shot2PtMade->value,
                    ActionType::Shot3PtMade->value,
                    ActionType::FtMade->value,
                ])
                ->sum('points');

            $flow[] = [
                'period' => $period->type,
                'points' => $points,
            ];
        }

        return $flow;
    }

    private function formatStat(int $made, int $attempted): array
    {
        $percentage = $attempted > 0
            ? round(($made / $attempted) * 100, 2)
            : 0;

        return [
            'made' => $made,
            'attempted' => $attempted,
            'percentage' => $percentage,
            'formatted' => "{$made}/{$attempted} ({$percentage}%)",
        ];
    }
}
