<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'jersey_number' => $this->jersey_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'position' => $this->position,
            'photo' => $this->photo
                ? (str_starts_with($this->photo, 'http')
                    ? $this->photo
                    : $request->getSchemeAndHttpHost() . '/storage/' . ltrim(str_replace('storage/', '', $this->photo), '/'))
                : null,
        ];
    }
}
