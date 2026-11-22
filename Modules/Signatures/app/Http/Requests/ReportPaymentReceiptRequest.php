<?php

namespace Modules\Signatures\Http\Requests;

use App\Enums\SignaturePaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A customer reports their transfer/deposit through the signed payment
 * link, attaching the receipt.
 */
class ReportPaymentReceiptRequest extends FormRequest
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
            'method' => ['required', 'string', Rule::in(array_map(
                fn (SignaturePaymentMethod $method) => $method->value,
                SignaturePaymentMethod::selfReported(),
            ))],
            'reference' => ['nullable', 'string', 'max:100'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['receipt' => 'comprobante', 'reference' => 'número de comprobante'];
    }
}
