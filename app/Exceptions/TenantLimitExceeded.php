<?php

namespace App\Exceptions;

use RuntimeException;

class TenantLimitExceeded extends RuntimeException
{
    public function __construct(
        public string $tenantId,
        public string $limitKey,
        public int $limit,
        public int $currentUsage,
        public int $requestedAmount,
    ) {
        parent::__construct("Tenant [{$tenantId}] exceeded limit [{$limitKey}].");
    }

    /**
     * @return array<string, int|string>
     */
    public function context(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'limit_key' => $this->limitKey,
            'limit' => $this->limit,
            'current_usage' => $this->currentUsage,
            'requested_amount' => $this->requestedAmount,
        ];
    }
}
