<?php

namespace Modules\Central\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLimitTypeRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('limit_types', 'key')],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
        ];
    }
}
