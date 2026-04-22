<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', 'unique:clients,name'],
            'client_key' => ['sometimes', 'string', 'max:255', 'unique:clients,client_key'],
            'is_active' => ['sometimes', 'boolean'],
            'webhook_secret' => ['sometimes', 'string', 'min:32'],
        ];
    }
}
