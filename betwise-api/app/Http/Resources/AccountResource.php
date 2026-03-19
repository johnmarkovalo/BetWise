<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'commission_pct' => (float) $this->commission_pct,
            'team_id' => $this->team_id,
            'team_name' => $this->whenLoaded('team', fn () => $this->team?->name),
            'status' => $this->status->value,
            'balance' => $this->whenLoaded('capital', fn () => (float) $this->capital?->balance),
            'locked' => $this->whenLoaded('capital', fn () => (float) $this->capital?->locked),
            'available' => $this->whenLoaded('capital', fn () => (float) $this->capital?->available),
            'min_balance_threshold' => (float) $this->min_balance_threshold,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
