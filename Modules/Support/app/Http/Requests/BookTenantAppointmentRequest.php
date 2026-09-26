<?php

namespace Modules\Support\Http\Requests;

use App\Models\SupportAttendanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookTenantAppointmentRequest extends FormRequest
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
            'support_attendance_type_id' => ['required', 'uuid', Rule::exists(SupportAttendanceType::class, 'id')->where('is_active', true)],
            'starts_at' => ['required', 'date', 'after:now'],
        ];
    }
}
