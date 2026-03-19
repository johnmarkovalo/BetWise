<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'account_count' => $this->whenLoaded('accounts', fn () => $this->accounts->count()),
            'total_commission' => $this->whenLoaded('accounts', fn () => round(
                $this->accounts->sum(fn ($a) => (float) $a->commission_pct),
                2
            )),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
