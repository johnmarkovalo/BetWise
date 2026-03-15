<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
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
            'provider' => ['required', 'string', 'max:255'],
            'commission_pct' => ['required', 'numeric', 'min:0'],
            'team_id' => ['sometimes', 'nullable', 'uuid', 'exists:teams,id'],
            'min_balance_threshold' => ['sometimes', 'numeric', 'min:0'],
            'initial_balance' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
