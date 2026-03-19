<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProxyProtocol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProxyRequest extends FormRequest
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
            'ip_address' => ['required', 'ip', Rule::unique('proxy_pool', 'ip_address')],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'protocol' => ['required', Rule::enum(ProxyProtocol::class)],
            'username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'geographic_region' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}
