<?php

namespace Modules\Central\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class SaveAttendanceTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'is_active' => ['boolean'],
        ];
    }
}
