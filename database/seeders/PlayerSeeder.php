<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $teamId = 1;

        $players = [
            ['jersey_number' => 0,  'first_name' => 'Taym',           'last_name' => 'Aldassouki',      'position' => 'SG'],
            ['jersey_number' => 1,  'first_name' => 'Falando Cortez', 'last_name' => 'Jones',           'position' => 'SF'],
            ['jersey_number' => 4,  'first_name' => 'Mhd Maiar',      'last_name' => 'Albalbisi',       'position' => 'SG'],
            ['jersey_number' => 6,  'first_name' => 'Nadim',          'last_name' => 'Issa',            'position' => 'SG'],
            ['jersey_number' => 7,  'first_name' => 'Abdullah',       'last_name' => 'Al Halabi',       'position' => 'PF'],
            ['jersey_number' => 8,  'first_name' => 'Majd',           'last_name' => 'Arbasha',         'position' => 'PG'],
            ['jersey_number' => 9,  'first_name' => 'Elias',          'last_name' => 'Azrie',           'position' => 'PG'],
            ['jersey_number' => 13, 'first_name' => 'Mohamad Bilal',  'last_name' => 'Atli',            'position' => 'PF'],
            ['jersey_number' => 14, 'first_name' => 'Majd',           'last_name' => 'Moakkad',         'position' => 'C'],
            ['jersey_number' => 23, 'first_name' => 'Hani',           'last_name' => 'Adribi',          'position' => 'PF'],
            ['jersey_number' => 33, 'first_name' => 'Christian James','last_name' => 'Maran',           'position' => 'PF'],
            ['jersey_number' => 54, 'first_name' => 'James',          'last_name' => 'Justice Jr',      'position' => 'PG'],
        ];

        foreach ($players as $player) {
            Player::create(array_merge($player, ['team_id' => $teamId]));
        }
    }
}
