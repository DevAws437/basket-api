<?php

namespace App\Services;

use App\Enums\PeriodType;
use App\Events\MatchUpdated;
use App\Models\MatchPeriod;
use App\Models\MatchRecord;
use Carbon\Carbon;

class MatchService
{
    public const QUARTER_DURATION = 600;
    public const OVERTIME_DURATION = 300;

    public function createMatch(array $data): MatchRecord
    {
        $match = MatchRecord::create([
            'type' => $data['type'],
            'team_id' => $data['team_id'],
            'opponent_name' => $data['opponent_name'],
            'team_score' => 0,
            'opponent_score' => 0,
            'status' => 'in_progress',
            'current_period' => 1,
            'is_paused' => false,
        ]);

        $this->createInitialPeriods($match);

        return $match->fresh();
    }

    private function createInitialPeriods(MatchRecord $match): void
    {
        $periodTypes = [PeriodType::Q1, PeriodType::Q2, PeriodType::Q3, PeriodType::Q4];

        foreach ($periodTypes as $index => $type) {
            MatchPeriod::create([
                'match_id' => $match->id,
                'period_number' => $index + 1,
                'type' => $type->value,
                'duration' => self::QUARTER_DURATION,
            ]);
        }
    }

    public function startMatch(MatchRecord $match): void
    {
        $firstPeriod = $match->periods()->orderBy('period_number')->first();
        if ($firstPeriod && !$firstPeriod->started_at) {
            $firstPeriod->update(['started_at' => now()]);
        }

        $match->update([
            'status' => 'in_progress',
            'is_paused' => false,
        ]);

        MatchUpdated::dispatch($match->fresh());
    }

    public function togglePause(MatchRecord $match): bool
    {
        $newPaused = !$match->is_paused;

        if ($newPaused) {
            $match->update(['is_paused' => true, 'paused_at' => now()]);
        } else {
            $pausedDuration = now()->diffInSeconds($match->paused_at ?? now());
            $match->update([
                'is_paused' => false,
                'paused_seconds' => ($match->paused_seconds ?? 0) + $pausedDuration,
                'paused_at' => null,
            ]);
        }

        MatchUpdated::dispatch($match->fresh());
        return $newPaused;
    }

    public function getCurrentPeriod(MatchRecord $match): ?MatchPeriod
    {
        return $match->periods()
            ->where('period_number', $match->current_period)
            ->first();
    }

    public function endPeriod(MatchRecord $match): array
    {
        $currentPeriod = $this->getCurrentPeriod($match);
        if ($currentPeriod) {
            $currentPeriod->update(['ended_at' => now()]);
        }

        $isLastRegulation = $match->current_period >= 4;
        $overtimeCount = $match->periods()->where('type', PeriodType::OT->value)->count();

        if ($isLastRegulation) {
            $isTied = $match->team_score === $match->opponent_score;

            if ($isTied) {
                $nextPeriodNumber = $match->current_period + 1;
                $overtimePeriod = MatchPeriod::create([
                    'match_id' => $match->id,
                    'period_number' => $nextPeriodNumber,
                    'type' => PeriodType::OT->value,
                    'duration' => self::OVERTIME_DURATION,
                ]);

                $match->update(['current_period' => $nextPeriodNumber]);
                MatchUpdated::dispatch($match->fresh());

                return [
                    'overtime_required' => true,
                    'period' => $overtimePeriod,
                ];
            }

            $match->update(['status' => 'completed']);
            MatchUpdated::dispatch($match->fresh());
            return [
                'match_ended' => true,
                'result' => $match->team_score > $match->opponent_score ? 'win' : 'loss',
            ];
        }

        $nextPeriod = $match->current_period + 1;
        $match->update(['current_period' => $nextPeriod]);
        MatchUpdated::dispatch($match->fresh());

        $nextPeriodModel = $match->periods()
            ->where('period_number', $nextPeriod)
            ->first();

        return [
            'period_ended' => true,
            'next_period' => $nextPeriodModel,
        ];
    }

    public function addOvertime(MatchRecord $match): MatchPeriod
    {
        $overtimeCount = $match->periods()->where('type', PeriodType::OT->value)->count();
        $nextPeriodNumber = $match->current_period + 1;

        $overtimePeriod = MatchPeriod::create([
            'match_id' => $match->id,
            'period_number' => $nextPeriodNumber,
            'type' => PeriodType::OT->value,
            'duration' => self::OVERTIME_DURATION,
        ]);

        $match->update(['current_period' => $nextPeriodNumber]);
        MatchUpdated::dispatch($match->fresh());

        return $overtimePeriod;
    }

    public function endMatch(MatchRecord $match): array
    {
        $currentPeriod = $this->getCurrentPeriod($match);
        if ($currentPeriod) {
            $currentPeriod->update(['ended_at' => now()]);
        }

        $match->update(['status' => 'completed']);
        MatchUpdated::dispatch($match->fresh());

        return [
            'match' => $match->fresh(),
            'result' => $match->team_score > $match->opponent_score ? 'win' : 'loss',
        ];
    }
}
