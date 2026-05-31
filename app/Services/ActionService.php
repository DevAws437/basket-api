<?php

namespace App\Services;

use App\Enums\ActionType;
use App\Events\MatchUpdated;
use App\Events\PlayerActionRecorded;
use App\Models\MatchPeriod;
use App\Models\MatchRecord;
use App\Models\PlayerAction;
use Illuminate\Support\Facades\DB;

class ActionService
{
    public function __construct(
        private LineupService $lineupService,
        private MatchService $matchService,
    ) {}

    public function recordAction(
        MatchRecord $match,
        int $playerId,
        string $actionType,
        ?int $periodId = null,
        ?int $relatedPlayerId = null
    ): PlayerAction {
        if ($match->status !== 'in_progress') {
            throw new \InvalidArgumentException('المباراة غير فعالة');
        }

        $lineup = $this->lineupService->getPlayerLineup($match, $playerId);
        if (!$lineup) {
            throw new \InvalidArgumentException('اللاعب غير موجود في التشكيلة');
        }

        try {
            $actionTypeEnum = ActionType::from($actionType);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException('نوع حدث غير صحيح');
        }

        if ($actionTypeEnum === ActionType::SubstitutionIn) {
            $benchLineup = $this->lineupService->getPlayerLineup($match, $playerId);
            if (!$benchLineup || $benchLineup->is_active) {
                throw new \InvalidArgumentException('اللاعب موجود أساساً في الملعب');
            }
            if (!$this->lineupService->canPlay($benchLineup)) {
                throw new \InvalidArgumentException('اللاعب مطرود بـ 5 أخطاء');
            }
        } else {
            if (!$lineup->is_active && $actionTypeEnum !== ActionType::SubstitutionOut) {
                throw new \InvalidArgumentException('اللاعب ليس في الملعب');
            }
            if (!$this->lineupService->canPlay($lineup)) {
                throw new \InvalidArgumentException('اللاعب مطرود بـ 5 أخطاء');
            }
        }

        $period = $periodId ? MatchPeriod::find($periodId) : $this->matchService->getCurrentPeriod($match);
        $now = now();

        if (!$period->started_at) {
            $period->update(['started_at' => $now]);
        }

        $actionTimestamp = $period->started_at
            ? $now->diffInSeconds($period->started_at)
            : 0;

        return DB::transaction(function () use ($match, $playerId, $actionTypeEnum, $period, $actionTimestamp, $relatedPlayerId) {
            $action = PlayerAction::create([
                'match_id' => $match->id,
                'player_id' => $playerId,
                'action_type' => $actionTypeEnum->value,
                'period_id' => $period?->id,
                'action_timestamp' => $actionTimestamp,
                'points' => $actionTypeEnum->points(),
                'related_player_id' => $relatedPlayerId,
                'is_undo' => false,
            ]);

            $this->applyActionEffects($match, $actionTypeEnum, $playerId, $relatedPlayerId);

            if ($actionTypeEnum === ActionType::Foul) {
                $this->lineupService->addFoul($match, $playerId);
                $lineup = $this->lineupService->getPlayerLineup($match, $playerId);
                if ($lineup && $this->lineupService->isFouledOut($lineup)) {
                    $action->load('player');
                    $action->force_foul_out = true;
                    $action->fouled_player_name = $lineup->player->last_name ?? '';
                }
            }

            $match->refresh();
            PlayerActionRecorded::dispatch($action);
            MatchUpdated::dispatch($match);
            return $action;
        });
    }

    public function substitute(MatchRecord $match, int $playerOutId, int $playerInId): array
    {
        $this->lineupService->substitute($match, $playerOutId, $playerInId);

        $period = $this->matchService->getCurrentPeriod($match);
        $now = now();
        $actionTimestamp = $period->started_at
            ? $now->diffInSeconds($period->started_at)
            : 0;

        $actionOut = PlayerAction::create([
            'match_id' => $match->id,
            'player_id' => $playerOutId,
            'action_type' => ActionType::SubstitutionOut->value,
            'period_id' => $period?->id,
            'action_timestamp' => $actionTimestamp,
            'points' => 0,
            'related_player_id' => $playerInId,
            'is_undo' => false,
        ]);

        $actionIn = PlayerAction::create([
            'match_id' => $match->id,
            'player_id' => $playerInId,
            'action_type' => ActionType::SubstitutionIn->value,
            'period_id' => $period?->id,
            'action_timestamp' => $actionTimestamp,
            'points' => 0,
            'related_player_id' => $playerOutId,
            'is_undo' => false,
        ]);

        PlayerActionRecorded::dispatch($actionOut);
        PlayerActionRecorded::dispatch($actionIn);
        MatchUpdated::dispatch($match);

        return [$actionOut, $actionIn];
    }

    public function undoLastAction(MatchRecord $match): ?PlayerAction
    {
        $lastAction = PlayerAction::where('match_id', $match->id)
            ->where('is_undo', false)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastAction) {
            return null;
        }

        return DB::transaction(function () use ($match, $lastAction) {
            $actionType = ActionType::from($lastAction->action_type);

            $this->reverseActionEffects($match, $actionType, $lastAction->player_id, $lastAction->related_player_id);

            if ($actionType === ActionType::Foul) {
                $this->lineupService->removeFoul($match, $lastAction->player_id);
            }

            $lastAction->update(['is_undo' => true]);
            $match->refresh();

            PlayerActionRecorded::dispatch($lastAction);
            MatchUpdated::dispatch($match);

            return $lastAction;
        });
    }

    private function applyActionEffects(MatchRecord $match, ActionType $type, int $playerId, ?int $relatedPlayerId): void
    {
        $points = $type->points();

        if ($points > 0) {
            $match->increment('team_score', $points);
        }

        if ($type === ActionType::SubstitutionOut && $relatedPlayerId) {
            $lineupOut = $this->lineupService->getPlayerLineup($match, $playerId);
            $lineupIn = $this->lineupService->getPlayerLineup($match, $relatedPlayerId);
            if ($lineupOut && $lineupOut->is_active && $lineupIn && !$lineupIn->is_active) {
                $this->lineupService->substitute($match, $playerId, $relatedPlayerId);
            }
        }
    }

    private function reverseActionEffects(MatchRecord $match, ActionType $type, int $playerId, ?int $relatedPlayerId): void
    {
        $points = $type->points();

        if ($points > 0) {
            $match->decrement('team_score', $points);
        }

        if ($type === ActionType::SubstitutionOut && $relatedPlayerId) {
            $lineupOut = $this->lineupService->getPlayerLineup($match, $relatedPlayerId);
            $lineupIn = $this->lineupService->getPlayerLineup($match, $playerId);
            if ($lineupOut && $lineupOut->is_active && $lineupIn && !$lineupIn->is_active) {
                $this->lineupService->substitute($match, $relatedPlayerId, $playerId);
            }
        }

        if ($type === ActionType::SubstitutionIn && $relatedPlayerId) {
            $lineupOut = $this->lineupService->getPlayerLineup($match, $playerId);
            $lineupIn = $this->lineupService->getPlayerLineup($match, $relatedPlayerId);
            if ($lineupOut && $lineupOut->is_active && $lineupIn && !$lineupIn->is_active) {
                $this->lineupService->substitute($match, $playerId, $relatedPlayerId);
            }
        }
    }

    public function updateOpponentScore(MatchRecord $match, int $score): void
    {
        $match->update(['opponent_score' => $score]);
        MatchUpdated::dispatch($match->fresh());
    }
}
