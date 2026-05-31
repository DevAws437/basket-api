<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            ['name' => 'Al-Wahda', 'is_populated' => true],
            ['name' => 'Al-Ittihad', 'is_populated' => false],
            ['name' => 'Al-Karamah', 'is_populated' => false],
            ['name' => 'Al-Jaish', 'is_populated' => false],
            ['name' => 'Al-Wathba', 'is_populated' => false],
            ['name' => 'Tishreen', 'is_populated' => false],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }
    }
}
