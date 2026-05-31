<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Models\Team;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::withCount('players')->get();
        return TeamResource::collection($teams);
    }

    public function show(Team $team)
    {
        $team->load('players');
        return new TeamResource($team);
    }
}
