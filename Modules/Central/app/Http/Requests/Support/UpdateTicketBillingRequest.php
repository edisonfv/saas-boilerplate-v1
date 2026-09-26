<?php

namespace Modules\Central\Http\Requests\Support;

use App\Enums\TicketBillingMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketBillingRequest extends FormRequest
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
            'billing_mode' => ['required', 'string', Rule::in(TicketBillingMode::toValues())],
            'fixed_amount' => [
                Rule::requiredIf($this->input('billing_mode') === TicketBillingMode::Fixed()->value),
                'nullable', 'numeric', 'min:0.01', 'max:99999999',
            ],
            'billing_reason' => ['required', 'string', 'max:1000'],
            'invoice_reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
