<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['name', 'logo', 'is_populated'];

    protected function casts(): array
    {
        return [
            'is_populated' => 'boolean',
        ];
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function matches()
    {
        return $this->hasMany(MatchRecord::class);
    }
}
