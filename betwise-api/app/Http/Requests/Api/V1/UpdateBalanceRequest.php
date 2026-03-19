<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBalanceRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'operation' => ['required', Rule::in(['add', 'subtract'])],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
