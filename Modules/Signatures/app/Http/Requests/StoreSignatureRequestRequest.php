<?php

namespace Modules\Signatures\Http\Requests;

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
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ];
    }

    protected function documentsAreRequired(): bool
    {
        return true;
    }
}
