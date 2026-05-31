<?php

namespace App\Models;

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
}
