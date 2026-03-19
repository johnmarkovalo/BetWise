<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProxyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'port' => $this->port,
            'protocol' => $this->protocol->value,
            'username' => $this->username,
            'geographic_region' => $this->geographic_region,
            'status' => $this->status->value,
            'health_score' => (float) $this->health_score,
            'total_uses' => $this->total_uses,
            'failed_uses' => $this->failed_uses,
            'banned_by_providers' => $this->banned_by_providers ?? [],
            'last_health_check' => $this->last_health_check?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
