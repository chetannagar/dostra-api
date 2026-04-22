<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('clients', 'name')->ignore($client),
            ],
            'client_key' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('clients', 'client_key')->ignore($client),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'webhook_secret' => ['sometimes', 'string', 'min:32'],
        ];
    }
}
