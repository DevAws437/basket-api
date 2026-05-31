<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lineup extends Model
{
    protected $fillable = [
        'match_id', 'player_id',
        'is_starting', 'is_active', 'fouls',
    ];

    protected function casts(): array
    {
        return [
            'is_starting' => 'boolean',
            'is_active' => 'boolean',
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
}
