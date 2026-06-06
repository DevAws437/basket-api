<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MatchRecord extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'type', 'team_id', 'opponent_name',
        'team_score', 'opponent_score',
        'status', 'current_period', 'is_paused',
    ];

    protected function casts(): array
    {
        return [
            'is_paused' => 'boolean',
        ];
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function periods()
    {
        return $this->hasMany(MatchPeriod::class, 'match_id');
    }

    public function actions()
    {
        return $this->hasMany(PlayerAction::class, 'match_id');
    }

    public function lineups()
    {
        return $this->hasMany(Lineup::class, 'match_id');
    }

    public function activePlayers()
    {
        return $this->hasMany(Lineup::class, 'match_id')->where('is_active', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getCurrentElapsedSeconds(): int
    {
        $period = $this->periods()
            ->where('period_number', $this->current_period)
            ->first();

        if (!$period || !$period->started_at) {
            return 0;
        }

        return max(0, Carbon::now()->diffInSeconds($period->started_at));
    }
}
