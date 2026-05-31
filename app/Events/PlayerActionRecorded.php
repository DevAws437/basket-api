<?php

namespace App\Events;

use App\Models\PlayerAction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class PlayerActionRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public PlayerAction $action
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("match.{$this->action->match_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'action' => [
                'id' => $this->action->id,
                'type' => $this->action->action_type,
                'player_id' => $this->action->player_id,
                'player_name' => $this->action->player?->name,
                'period_id' => $this->action->period_id,
                'related_player_id' => $this->action->related_player_id,
                'is_undo' => $this->action->is_undo,
                'created_at' => $this->action->created_at,
            ],
            'match_id' => $this->action->match_id,
        ];
    }
}
