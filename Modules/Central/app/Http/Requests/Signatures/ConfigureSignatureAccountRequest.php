<?php

namespace Modules\Central\Http\Requests\Signatures;

use App\Enums\SignatureAffiliationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigureSignatureAccountRequest extends FormRequest
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
            'affiliation_mode' => ['required', 'string', Rule::in(SignatureAffiliationMode::toValues())],
            'credit_limit' => [
                Rule::requiredIf($this->input('affiliation_mode') === SignatureAffiliationMode::Credit()->value),
                'nullable', 'numeric', 'min:0', 'max:9999999',
            ],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
