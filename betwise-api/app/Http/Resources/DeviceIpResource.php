<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceIpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'ip_address' => $this->ip_address,
            'ip_type' => $this->ip_type->value,
            'proxy_config' => $this->proxy_config,
            'active_from' => $this->active_from?->toISOString(),
            'active_until' => $this->active_until?->toISOString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
