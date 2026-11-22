<?php

namespace App\Services;

use App\Models\Tenant;

/**
 * Shapes the tenant identity shown at the top of every tab of a tenant in
 * the central console (summary, electronic signatures…).
 */
class TenantPresenter
{
    /**
     * @return array{id: string, company_name: string|null, status: string, status_label: string, domain: string|null, subscription_status: string|null, subscription_status_label: string|null, created_at: mixed}
     */
    public function header(Tenant $tenant): array
    {
        $tenant->loadMissing(['domains', 'subscription']);

        return [
            'id' => $tenant->getTenantKey(),
            'company_name' => $tenant->company_name,
            'status' => $tenant->operationalStatusValue(),
            'status_label' => $tenant->operationalStatusLabel(),
            'domain' => $tenant->domains->first()?->domain,
            'subscription_status' => $tenant->subscription?->status?->value,
            'subscription_status_label' => $tenant->subscription?->status?->label,
            'created_at' => $tenant->created_at,
        ];
    }
}
