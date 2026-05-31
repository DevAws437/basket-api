<?php

namespace App\Services;

use App\Models\Lineup;
use App\Models\MatchRecord;

class LineupService
{
    public function createInitialLineup(MatchRecord $match, array $playerIds): void
    {
        foreach ($playerIds as $playerId) {
            Lineup::create([
                'match_id' => $match->id,
                'player_id' => $playerId,
                'is_starting' => true,
                'is_active' => true,
                'fouls' => 0,
            ]);
        }
    }

    public function addBenchPlayers(MatchRecord $match, array $allPlayerIds, array $startingIds): void
    {
        foreach ($allPlayerIds as $playerId) {
            if (in_array($playerId, $startingIds)) {
                continue;
            }

            Lineup::create([
                'match_id' => $match->id,
                'player_id' => $playerId,
                'is_starting' => false,
                'is_active' => false,
                'fouls' => 0,
            ]);
        }
    }

    public function getActivePlayers(MatchRecord $match)
    {
        return Lineup::where('match_id', $match->id)
            ->where('is_active', true)
            ->with('player')
            ->get();
    }

    public function getBenchPlayers(MatchRecord $match)
    {
        return Lineup::where('match_id', $match->id)
            ->where('is_active', false)
            ->where('fouls', '<', 5)
            ->with('player')
            ->get();
    }

    public function getPlayerLineup(MatchRecord $match, int $playerId): ?Lineup
    {
        return Lineup::where('match_id', $match->id)
            ->where('player_id', $playerId)
            ->first();
    }

    public function canPlay(Lineup $lineup): bool
    {
        return $lineup->fouls < 5;
    }

    public function substitute(MatchRecord $match, int $playerOutId, int $playerInId): void
    {
        $lineupOut = $this->getPlayerLineup($match, $playerOutId);
        $lineupIn = $this->getPlayerLineup($match, $playerInId);

        if (!$lineupOut || !$lineupOut->is_active) {
            throw new \InvalidArgumentException('اللاعب الخارج ليس في الملعب');
        }

        if (!$lineupIn || $lineupIn->is_active) {
            throw new \InvalidArgumentException('اللاعب الداخل موجود أساساً في الملعب');
        }

        if (!$this->canPlay($lineupIn)) {
            throw new \InvalidArgumentException('اللاعب مطرود بـ 5 أخطاء ولا يمكنه الدخول');
        }

        $lineupOut->update(['is_active' => false]);
        $lineupIn->update(['is_active' => true]);
    }

    public function addFoul(MatchRecord $match, int $playerId): void
    {
        $lineup = $this->getPlayerLineup($match, $playerId);
        if (!$lineup) {
            throw new \InvalidArgumentException('اللاعب غير موجود في التشكيلة');
        }

        $lineup->increment('fouls');
    }

    public function removeFoul(MatchRecord $match, int $playerId): void
    {
        $lineup = $this->getPlayerLineup($match, $playerId);
        if ($lineup && $lineup->fouls > 0) {
            $lineup->decrement('fouls');
        }
    }

    public function isFouledOut(Lineup $lineup): bool
    {
        return $lineup->fouls >= 5;
    }

    public function getActivePlayerIds(MatchRecord $match): array
    {
        return Lineup::where('match_id', $match->id)
            ->where('is_active', true)
            ->pluck('player_id')
            ->toArray();
    }
}
