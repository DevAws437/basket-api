<?php

namespace App\Services;

use App\Models\MatchRecord;

class MVPCalculator
{
    public function __construct(
        private StatisticsService $statisticsService,
    ) {}

    public function calculate(MatchRecord $match): ?array
    {
        $playerStats = $this->statisticsService->getAllPlayerStats($match);

        if (empty($playerStats)) {
            return null;
        }

        usort($playerStats, function (array $a, array $b) {
            if ($a['efficiency'] !== $b['efficiency']) {
                return $b['efficiency'] <=> $a['efficiency'];
            }

            if ($a['points'] !== $b['points']) {
                return $b['points'] <=> $a['points'];
            }

            $aThrees = $a['three_point']['made'] ?? 0;
            $bThrees = $b['three_point']['made'] ?? 0;

            return $bThrees <=> $aThrees;
        });

        $mvp = $playerStats[0];

        return [
            'player_id' => $mvp['player_id'],
            'player_name' => $mvp['player_name'],
            'jersey_number' => $mvp['jersey_number'],
            'position' => $mvp['position'],
            'team_name' => $match->team->name,
            'points' => $mvp['points'],
            'efficiency' => $mvp['efficiency'],
            'stats' => $mvp,
        ];
    }
}
