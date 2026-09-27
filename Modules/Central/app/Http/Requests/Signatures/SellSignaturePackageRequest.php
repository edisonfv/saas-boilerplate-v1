<?php

namespace Modules\Central\Http\Requests\Signatures;

use App\Models\SignaturePackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellSignaturePackageRequest extends FormRequest
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
            'signature_package_id' => ['required', 'string', Rule::exists(SignaturePackage::class, 'id')->where('is_active', true)],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
