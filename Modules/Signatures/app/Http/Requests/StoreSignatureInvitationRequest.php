<?php

namespace Modules\Signatures\Http\Requests;

use App\Enums\SignaturePaymentMethod;
use App\Models\SignatureProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A prepaid single-use link: the product, who it's for and the payment
 * already collected.
 */
class StoreSignatureInvitationRequest extends FormRequest
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
            'signature_product_id' => ['required', 'string', Rule::exists(SignatureProduct::class, 'id')->where('is_active', true)],
            'customer_name' => ['required', 'string', 'max:160'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'regex:/^09\d{8}$/'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999'],
            'method' => ['required', 'string', Rule::in(SignaturePaymentMethod::toValues())],
            'reference' => [
                Rule::requiredIf($this->input('method') !== SignaturePaymentMethod::Cash()->value),
                'nullable', 'string', 'max:100',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'signature_product_id' => 'firma',
            'customer_name' => 'nombre del cliente',
            'customer_email' => 'correo del cliente',
            'customer_phone' => 'celular del cliente',
            'amount' => 'monto cobrado',
            'reference' => 'número de comprobante',
        ];
    }
}
