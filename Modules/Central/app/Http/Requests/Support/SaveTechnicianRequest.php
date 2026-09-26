<?php

namespace Modules\Central\Http\Requests\Support;

use App\Models\CentralUser;
use App\Models\SupportTechnician;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTechnicianRequest extends FormRequest
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
            'central_user_id' => [
                Rule::requiredIf($this->route('technician') === null),
                'uuid',
                Rule::exists(CentralUser::class, 'id'),
                Rule::unique(SupportTechnician::class, 'central_user_id')->ignore($this->route('technician')),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['boolean'],
            'shifts' => ['present', 'array', 'max:28'],
            'shifts.*.weekday' => ['required', 'integer', 'between:1,7'],
            'shifts.*.starts_at' => ['required', 'date_format:H:i'],
            'shifts.*.ends_at' => ['required', 'date_format:H:i', 'after:shifts.*.starts_at'],
        ];
    }
}
