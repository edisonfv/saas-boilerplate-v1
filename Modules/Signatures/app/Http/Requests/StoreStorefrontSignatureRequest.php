<?php

namespace Modules\Signatures\Http\Requests;

/**
 * A request an end customer leaves on the tenant's public storefront. It
 * lands as a draft for an operator to review, collect payment and submit.
 */
class StoreStorefrontSignatureRequest extends SignatureApplicationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'accepts_terms' => ['accepted'],
        ];
    }

    protected function documentsAreRequired(): bool
    {
        return true;
    }
}
