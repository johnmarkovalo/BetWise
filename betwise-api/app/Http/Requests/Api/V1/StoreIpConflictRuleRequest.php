<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIpConflictRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:50', Rule::unique('ip_conflict_rules', 'provider')],
            'max_concurrent_devices' => ['required', 'integer', 'min:1'],
            'cooldown_seconds' => ['required', 'integer', 'min:0'],
            'hourly_limit' => ['required', 'integer', 'min:1'],
            'require_unique_per_team' => ['required', 'boolean'],
        ];
    }
}
