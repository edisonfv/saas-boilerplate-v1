<?php

namespace Modules\Central\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupportSettingsRequest extends FormRequest
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
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['required', 'string', 'size:3', 'alpha:ascii'],
            'booking_min_notice_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'booking_max_days_ahead' => ['required', 'integer', 'min:1', 'max:365'],
            'cancellation_notice_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'auto_close_days' => ['required', 'integer', 'min:1', 'max:365'],
            'reopen_window_days' => ['required', 'integer', 'min:0', 'max:365'],
            'rating_link_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['currency' => $this->string('currency')->upper()->toString()]);
    }
}
