<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property string|null $company_name
 * @property string|null $legal_name
 * @property string|null $tax_identifier
 * @property string|null $country_code
 * @property string $timezone
 * @property string|null $primary_contact_name
 * @property string|null $primary_contact_email
 * @property TenantStatus $status
 * @property Carbon|null $provisioned_at
 */
#[Fillable(['id', 'company_name', 'legal_name', 'tax_identifier', 'country_code', 'timezone', 'primary_contact_name', 'primary_contact_email', 'status', 'provisioned_at'])]
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $attributes = [
        'status' => 'Active',
        'timezone' => 'UTC',
    ];

    /**
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'tenant_id', 'id');
    }

    public function operationalStatus(): TenantStatus
    {
        $status = $this->getRawOriginal('status') ?? $this->attributes['status'] ?? TenantStatus::Active()->value;

        return match ($status) {
            TenantStatus::Provisioning()->value, 'provisioning' => TenantStatus::Provisioning(),
            TenantStatus::Suspended()->value, 'suspended' => TenantStatus::Suspended(),
            default => TenantStatus::Active(),
        };
    }

    public function operationalStatusValue(): string
    {
        return (string) $this->operationalStatus()->value;
    }

    public function operationalStatusLabel(): string
    {
        return $this->operationalStatus()->label;
    }

    /**
     * @return list<string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'company_name',
            'legal_name',
            'tax_identifier',
            'country_code',
            'timezone',
            'primary_contact_name',
            'primary_contact_email',
            'status',
            'provisioned_at',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'provisioned_at' => 'datetime',
        ];
    }
}
