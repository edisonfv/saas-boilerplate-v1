<?php

namespace App\Services\Signatures\Contracts;

use App\Services\Signatures\ProviderStatusUpdate;
use App\Services\Signatures\SignatureApplication;
use App\Services\Signatures\SignatureProviderException;
use Illuminate\Http\Request;

/**
 * Port to the certification authority that actually issues signatures.
 * The rest of the module (wallet, issuance, webhooks) only talks to this
 * interface, so switching or adding a provider means a new adapter bound
 * in AppServiceProvider — nothing else changes.
 */
interface SignatureProvider
{
    /**
     * Stable identifier stored with each submission (e.g. "uanataca").
     */
    public function key(): string;

    /**
     * Send an application for issuance.
     *
     * @return string the provider's token for the request, used to
     *                correlate later status webhooks
     *
     * @throws SignatureProviderException when the provider refuses it or
     *                                    can't be reached
     */
    public function submit(SignatureApplication $application): string;

    /**
     * Whether an incoming webhook really comes from the provider.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Translate a webhook body into a provider-agnostic status update.
     *
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(array $payload): ProviderStatusUpdate;
}
