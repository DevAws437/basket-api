<?php

namespace App\Enums;

enum PlayerPosition: string
{
    case PG = 'PG';
    case SG = 'SG';
    case SF = 'SF';
    case PF = 'PF';
    case C = 'C';

    public function label(): string
    {
        return match ($this) {
            self::PG => 'Point Guard',
            self::SG => 'Shooting Guard',
            self::SF => 'Small Forward',
            self::PF => 'Power Forward',
            self::C => 'Center',
        };
    }
}
