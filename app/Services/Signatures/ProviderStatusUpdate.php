<?php

namespace App\Services\Signatures;

use App\Enums\SignatureRequestStatus;
use Carbon\CarbonImmutable;

/**
 * Provider-agnostic status change of a submitted request, produced by a
 * SignatureProvider from its webhook payload.
 */
final readonly class ProviderStatusUpdate
{
    public function __construct(
        public string $providerToken,
        public SignatureRequestStatus $status,
        public string $eventType,
        public string $description,
        public ?string $certificateSerial = null,
        public ?CarbonImmutable $certificateValidFrom = null,
        public ?CarbonImmutable $certificateValidTo = null,
    ) {}
}
