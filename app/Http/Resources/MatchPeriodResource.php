<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period_number' => $this->period_number,
            'type' => $this->type,
            'duration' => $this->duration,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
        ];
    }
}
