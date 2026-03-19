<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IpConflictRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'max_concurrent_devices' => $this->max_concurrent_devices,
            'cooldown_seconds' => $this->cooldown_seconds,
            'hourly_limit' => $this->hourly_limit,
            'require_unique_per_team' => $this->require_unique_per_team,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
