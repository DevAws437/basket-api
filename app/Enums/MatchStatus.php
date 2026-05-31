<?php

namespace App\Enums;

enum MatchStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
