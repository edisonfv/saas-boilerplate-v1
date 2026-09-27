<?php

namespace Modules\Signatures\Http\Requests;

/**
 * Editing a draft: documents already on file don't need to be re-uploaded
 * (missing ones are enforced when the request is submitted).
 */
class UpdateSignatureRequestRequest extends SignatureApplicationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ];
    }

    protected function documentsAreRequired(): bool
    {
        return false;
    }
}
