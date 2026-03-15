<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'table_id' => $this->table_id,
            'status' => $this->status->value,
            'teams' => $this->whenLoaded('teams', fn () => $this->teams->map(fn ($team) => [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'side' => $team->pivot->side->value,
            ])),
            'locked_at' => $this->locked_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
