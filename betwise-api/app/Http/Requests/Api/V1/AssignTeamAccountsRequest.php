<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AssignTeamAccountsRequest extends FormRequest
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
            'account_ids' => ['required', 'array', 'min:1'],
            'account_ids.*' => ['required', 'uuid', 'exists:accounts,id'],
        ];
    }
}
