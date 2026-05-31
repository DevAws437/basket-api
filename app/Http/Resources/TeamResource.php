<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'is_populated' => $this->is_populated,
            'players_count' => $this->whenCounted('players'),
            'players' => PlayerResource::collection($this->whenLoaded('players')),
        ];
    }
}
