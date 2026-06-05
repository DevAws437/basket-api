<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo
                ? (str_starts_with($this->logo, 'http')
                    ? $this->logo
                    : $request->getSchemeAndHttpHost() . '/storage/' . ltrim(str_replace('/storage/', '', $this->logo), '/'))
                : null,
            'is_populated' => $this->is_populated,
            'players_count' => $this->whenCounted('players'),
            'players' => PlayerResource::collection($this->whenLoaded('players')),
        ];
    }
}
