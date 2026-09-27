<?php

namespace Modules\Central\Http\Requests\Signatures;

use App\Models\SignatureProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustSignatureUnitsRequest extends FormRequest
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
            'signature_product_id' => ['required', 'string', Rule::exists(SignatureProduct::class, 'id')],
            'units' => ['required', 'integer', 'not_in:0', 'between:-10000,10000'],
            'description' => ['required', 'string', 'max:500'],
        ];
    }
}
