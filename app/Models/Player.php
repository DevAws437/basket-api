<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = ['team_id', 'jersey_number', 'first_name', 'last_name', 'position', 'photo'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function actions()
    {
        return $this->hasMany(PlayerAction::class);
    }

    public function lineups()
    {
        return $this->hasMany(Lineup::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getLastNameOnlyAttribute(): string
    {
        return $this->last_name;
    }
}
