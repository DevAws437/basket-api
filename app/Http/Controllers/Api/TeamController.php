<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::withCount('players')->get();
        return TeamResource::collection($teams);
    }

<<<<<<< HEAD
    public function store(StoreTeamRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('teams', 'public');
        }

        $team = Team::create($data);
        return new TeamResource($team, 201);
    }

=======
public function store(StoreTeamRequest $request)
{
    dd([
        'all' => $request->all(),
        'hasFile' => $request->hasFile('logo'),
        'file' => $request->file('logo'),
    ]);
}
>>>>>>> 364522a36e8593377b3611f16ac81a7f66bea18b
    public function show(Team $team)
    {
        $team->load('players');
        return new TeamResource($team);
    }

<<<<<<< HEAD
    public function update(UpdateTeamRequest $request, Team $team)
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($team->logo) {
                Storage::disk('public')->delete($team->logo);
            }
            $data['logo'] = $request->file('logo')->store('teams', 'public');
        }

        $team->update($data);
        return new TeamResource($team->fresh()->load('players'));
=======
   public function update(UpdateTeamRequest $request, Team $team)
{
    $data = $request->validated();

    if ($request->hasFile('logo')) {
        $data['logo'] = $request->file('logo')->store('teams', 'public');
>>>>>>> 364522a36e8593377b3611f16ac81a7f66bea18b
    }

    $team->update($data);

    return new TeamResource($team->fresh()->load('players'));
}

    public function destroy(Team $team)
    {
        if ($team->matches()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف فريق لديه مباريات مسجلة'], 422);
        }
        if ($team->logo) {
            Storage::disk('public')->delete($team->logo);
        }
        $team->delete();
        return response()->json(['message' => 'تم حذف الفريق بنجاح']);
    }
}
