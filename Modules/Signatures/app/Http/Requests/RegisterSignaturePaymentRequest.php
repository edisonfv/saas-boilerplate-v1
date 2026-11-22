<?php

namespace Modules\Signatures\Http\Requests;

use App\Enums\SignaturePaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Staff registers a payment it has verified for a signature request.
 */
class RegisterSignaturePaymentRequest extends FormRequest
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
            'method' => ['required', 'string', Rule::in(SignaturePaymentMethod::toValues())],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999'],
            'reference' => [
                Rule::requiredIf($this->input('method') !== SignaturePaymentMethod::Cash()->value),
                'nullable', 'string', 'max:100',
            ],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reference' => 'número de comprobante', 'amount' => 'monto'];
    }
}
