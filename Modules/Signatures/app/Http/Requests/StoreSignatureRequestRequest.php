<?php

namespace Modules\Signatures\Http\Requests;

use App\Rules\RetailPriceFloor;

/**
 * A request captured by a tenant operator at the point of sale.
 */
class StoreSignatureRequestRequest extends SignatureApplicationRequest
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
        return true;
    }
}
