<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::withCount('players')->get();
        return TeamResource::collection($teams);
    }

    public function store(StoreTeamRequest $request)
    {
        $team = Team::create($request->validated());
        return new TeamResource($team, 201);
    }

    public function show(Team $team)
    {
        $team->load('players');
        return new TeamResource($team);
    }

    public function update(UpdateTeamRequest $request, Team $team)
    {
        $team->update($request->validated());
        return new TeamResource($team->fresh()->load('players'));
    }

    public function destroy(Team $team)
    {
        if ($team->matches()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف فريق لديه مباريات مسجلة'], 422);
        }
        $team->delete();
        return response()->json(['message' => 'تم حذف الفريق بنجاح']);
    }
}
