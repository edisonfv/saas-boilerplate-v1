<?php

namespace Modules\Signatures\Http\Requests;

use App\Rules\RetailPriceFloor;

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
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:99999', new RetailPriceFloor($this->string('signature_product_id')->toString())],
        ];
    }

    protected function documentsAreRequired(): bool
    {
        return false;
    }
}
