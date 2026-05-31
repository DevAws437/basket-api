<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchPeriod extends Model
{
    protected $fillable = [
        'match_id', 'period_number', 'type',
        'duration', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function match()
    {
        return $this->belongsTo(MatchRecord::class, 'match_id');
    }

    public function actions()
    {
        return $this->hasMany(PlayerAction::class, 'period_id');
    }
}
