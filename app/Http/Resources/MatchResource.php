<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'team' => new TeamResource($this->whenLoaded('team')),
            'opponent_name' => $this->opponent_name,
            'team_score' => $this->team_score,
            'opponent_score' => $this->opponent_score,
            'status' => $this->status,
            'current_period' => $this->current_period,
            'is_paused' => $this->is_paused,
            'paused_seconds' => $this->paused_seconds,
            'elapsed_seconds' => $this->getCurrentElapsedSeconds(),
            'result' => $this->when($this->status === 'completed', function () {
                return $this->team_score > $this->opponent_score ? 'win' : 'loss';
            }),
            'periods' => MatchPeriodResource::collection($this->whenLoaded('periods')),
            'active_players' => PlayerResource::collection($this->whenLoaded('activePlayers')),
            'created_at' => $this->created_at,
        ];
    }
}
