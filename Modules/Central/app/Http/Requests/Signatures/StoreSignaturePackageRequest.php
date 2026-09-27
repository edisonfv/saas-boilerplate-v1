<?php

namespace Modules\Central\Http\Requests\Signatures;

use Illuminate\Foundation\Http\FormRequest;

class StoreSignaturePackageRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ];
    }
}
