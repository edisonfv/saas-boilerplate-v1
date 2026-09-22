<?php

namespace App\Services;

use App\Enums\BillingPeriod;
use App\Enums\TenantStatus;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Throwable;

class TenantProvisioner
{
    public function __construct(private TenantPlanSubscriber $subscriber) {}

    /**
     * @param  array{id: string, company_name: string, legal_name: string|null, tax_identifier: string|null, country_code: string, timezone: string, owner_name: string, owner_email: string, owner_password: string}  $attributes
     */
    public function provision(array $attributes, Plan $plan, BillingPeriod $billingPeriod): Tenant
    {
        $tenant = null;

        try {
            $tenant = Tenant::create([
                'id' => $attributes['id'],
                'company_name' => $attributes['company_name'],
                'legal_name' => $attributes['legal_name'],
                'tax_identifier' => $attributes['tax_identifier'],
                'country_code' => $attributes['country_code'],
                'timezone' => $attributes['timezone'],
                'primary_contact_name' => $attributes['owner_name'],
                'primary_contact_email' => $attributes['owner_email'],
                'status' => TenantStatus::Provisioning(),
            ]);

            $tenant->createDomain("{$tenant->getTenantKey()}.".config('tenancy.central_domains.0'));
            $this->createOwner($tenant, $attributes['owner_name'], $attributes['owner_email'], $attributes['owner_password']);
            $this->subscriber->subscribe($tenant, $plan, $billingPeriod);

            $tenant->update([
                'status' => TenantStatus::Active(),
                'provisioned_at' => now(),
            ]);

            return $tenant;
        } catch (Throwable $exception) {
            $tenant?->delete();

            throw $exception;
        }
    }

    private function createOwner(Tenant $tenant, string $name, string $email, string $password): void
    {
        $tenant->run(function () use ($name, $email, $password): void {
            $owner = Role::query()->where('name', 'owner')->where('guard_name', 'web')->sole();
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $user->assignRole($owner);
        });
    }
}
