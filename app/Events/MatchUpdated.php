<?php

namespace App\Events;

use App\Models\MatchRecord;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class MatchUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public MatchRecord $match
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("match.{$this->match->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'match' => [
                'id' => $this->match->id,
                'status' => $this->match->status,
                'team_score' => $this->match->team_score,
                'opponent_score' => $this->match->opponent_score,
                'current_period' => $this->match->currentPeriod?->period_number,
                'is_paused' => $this->match->is_paused,
            ],
        ];
    }
}
