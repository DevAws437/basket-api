<?php

namespace App\Enums;

enum ActionType: string
{
    case Shot2PtMade = 'shot_2pt_made';
    case Shot2PtMissed = 'shot_2pt_missed';
    case Shot3PtMade = 'shot_3pt_made';
    case Shot3PtMissed = 'shot_3pt_missed';
    case FtMade = 'ft_made';
    case FtMissed = 'ft_missed';
    case Rebound = 'rebound';
    case Assist = 'assist';
    case Steal = 'steal';
    case Turnover = 'turnover';
    case Foul = 'foul';
    case SubstitutionOut = 'substitution_out';
    case SubstitutionIn = 'substitution_in';

    public function points(): int
    {
        return match ($this) {
            self::Shot2PtMade => 2,
            self::Shot3PtMade => 3,
            self::FtMade => 1,
            default => 0,
        };
    }

    public function isShot(): bool
    {
        return in_array($this, [
            self::Shot2PtMade,
            self::Shot2PtMissed,
            self::Shot3PtMade,
            self::Shot3PtMissed,
            self::FtMade,
            self::FtMissed,
        ]);
    }

    public function isMade(): bool
    {
        return in_array($this, [
            self::Shot2PtMade,
            self::Shot3PtMade,
            self::FtMade,
        ]);
    }
}
