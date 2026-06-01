<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;

class PlayerController extends Controller
{
    public function index()
    {
        $players = Player::with('team')->orderBy('team_id')->orderBy('jersey_number')->get();
        return PlayerResource::collection($players);
    }

    public function store(StorePlayerRequest $request)
    {
        $player = Player::create($request->validated());
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
        $player->update($request->validated());
        return new PlayerResource($player->fresh()->load('team'));
    }

    public function destroy(Player $player)
    {
        $player->delete();
        return response()->json(['message' => 'تم حذف اللاعب بنجاح']);
    }
}
