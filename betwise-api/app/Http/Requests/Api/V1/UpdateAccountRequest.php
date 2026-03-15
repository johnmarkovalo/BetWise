<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AccountStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
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
            'provider' => ['sometimes', 'string', 'max:255'],
            'commission_pct' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::enum(AccountStatus::class)],
            'team_id' => ['sometimes', 'nullable', 'uuid', 'exists:teams,id'],
            'min_balance_threshold' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
