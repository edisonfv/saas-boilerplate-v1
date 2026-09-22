<?php

namespace Modules\Central\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('plans', 'slug')],
            'trial_days' => ['nullable', 'integer', 'min:0'],

            'prices' => ['array'],
            'prices.*.enabled' => ['boolean'],
            'prices.*.price' => ['nullable', 'numeric', 'min:0'],
            'prices.*.currency' => ['nullable', 'string', 'size:3'],

            'modules' => ['array'],
            'modules.*' => ['uuid', 'exists:modules,id'],

            'features' => ['array'],
            'features.*' => ['uuid', 'exists:features,id'],

            'limits' => ['array'],
            'limits.*' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
