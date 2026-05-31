<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerAction extends Model
{
    protected $fillable = [
        'match_id', 'player_id', 'action_type',
        'period_id', 'action_timestamp', 'points',
        'related_player_id', 'is_undo',
    ];

    protected function casts(): array
    {
        return [
            'is_undo' => 'boolean',
        ];
    }

    public function match()
    {
        return $this->belongsTo(MatchRecord::class, 'match_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function period()
    {
        return $this->belongsTo(MatchPeriod::class, 'period_id');
    }

    public function relatedPlayer()
    {
        return $this->belongsTo(Player::class, 'related_player_id');
    }
}
