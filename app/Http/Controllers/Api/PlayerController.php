<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Support\Facades\Storage;

class PlayerController extends Controller
{
    public function index()
    {
        $players = Player::with('team')->orderBy('team_id')->orderBy('jersey_number')->get();
        return PlayerResource::collection($players);
    }

    public function store(StorePlayerRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('players', 'public');
        }

        $player = Player::create($data);
        $player->load('team');
        return new PlayerResource($player);
    }

    public function show(Player $player)
    {
        $player->load('team');
        return new PlayerResource($player);
    }

    public function update(UpdatePlayerRequest $request, Player $player)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($player->photo) {
                Storage::disk('public')->delete($player->photo);
            }
            $data['photo'] = $request->file('photo')->store('players', 'public');
        }

        $player->update($data);
        return new PlayerResource($player->fresh()->load('team'));
    }

    public function destroy(Player $player)
    {
        if ($player->photo) {
            Storage::disk('public')->delete($player->photo);
        }
        $player->delete();
        return response()->json(['message' => 'تم حذف اللاعب بنجاح']);
    }
}
