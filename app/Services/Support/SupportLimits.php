<?php

namespace App\Services\Support;

/**
 * Plan limit keys (App\Models\LimitType::$key) that drive the Support
 * module. They're frozen on each subscription like any other limit, so
 * editing a plan never changes what existing tenants contracted.
 */
final class SupportLimits
{
    /** Support hours included per billing cycle. */
    public const IncludedHours = 'support_hours';

    /** Maximum simultaneously scheduled appointments per tenant. */
    public const MaxActiveAppointments = 'support_active_appointments';

    /** Business hours to first response for a Normal-priority ticket. */
    public const FirstResponseHours = 'support_sla_first_response_hours';

    /** Business hours to resolution for a Normal-priority ticket. */
    public const ResolutionHours = 'support_sla_resolution_hours';

    /** Slug of the sellable Support module in the catalog. */
    public const ModuleSlug = 'support';
}
