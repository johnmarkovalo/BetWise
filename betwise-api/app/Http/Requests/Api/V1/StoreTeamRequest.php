<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(TeamRole::class)],
            'status' => ['sometimes', Rule::enum(TeamStatus::class)],
        ];
    }
}
