<?php

namespace Modules\Signatures\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStorefrontRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'headline' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'regex:/^\+?\d{9,15}$/'],
            'whatsapp_message' => ['nullable', 'string', 'max:500'],
            'prices' => ['nullable', 'array'],
            'prices.*' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ];
    }
}
