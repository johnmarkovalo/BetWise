<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatchupRequest extends FormRequest
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
            'table_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'primary_team_id' => ['required', 'uuid', 'exists:teams,id'],
            'counter_team_id' => ['required', 'uuid', 'exists:teams,id'],
        ];
    }
}
