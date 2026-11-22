<?php

namespace Modules\Central\Http\Requests\Signatures;

use App\Enums\SignatureContainer;
use App\Enums\SignatureValidity;
use App\Models\SignatureProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSignatureProductRequest extends FormRequest
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
        /** @var SignatureProduct|null $product */
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'validity' => ['required', 'string', Rule::in(SignatureValidity::toValues())],
            'container' => [
                'required', 'string', Rule::in(SignatureContainer::toValues()),
                Rule::unique(SignatureProduct::class, 'container')
                    ->where('validity', $this->input('validity'))
                    ->ignore($product?->id),
            ],
            'provider_cost' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'credit_unit_price' => ['required', 'numeric', 'min:0', 'max:99999'],
            'suggested_retail_price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'min_retail_price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'container.unique' => 'Ya existe un producto con esa vigencia y ese contenedor.',
        ];
    }
}
