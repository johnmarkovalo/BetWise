<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIpConflictRuleRequest extends FormRequest
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
        /** @var \App\Models\IpConflictRule $rule */
        $rule = $this->route('ip_conflict_rule');

        return [
            'provider' => ['sometimes', 'string', 'max:50', Rule::unique('ip_conflict_rules', 'provider')->ignore($rule->id)],
            'max_concurrent_devices' => ['sometimes', 'integer', 'min:1'],
            'cooldown_seconds' => ['sometimes', 'integer', 'min:0'],
            'hourly_limit' => ['sometimes', 'integer', 'min:1'],
            'require_unique_per_team' => ['sometimes', 'boolean'],
        ];
    }
}
