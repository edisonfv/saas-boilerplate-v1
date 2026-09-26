<?php

namespace Database\Seeders;

use App\Enums\Action;
use App\Enums\BillingPeriod;
use App\Models\Feature;
use App\Models\LimitType;
use App\Models\Module;
use App\Models\Plan;
use App\Services\Support\SupportLimits;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    private const PRO_SUPPORT_LIMITS = [
        SupportLimits::IncludedHours => 4,
        SupportLimits::MaxActiveAppointments => 2,
        SupportLimits::FirstResponseHours => 4,
        SupportLimits::ResolutionHours => 24,
    ];

    /**
     * Seed an example catalog: modules, features, limits, permissions and plans.
     */
    public function run(): void
    {
        $crm = Module::factory()->create(['slug' => 'crm', 'name' => 'CRM']);
        $projects = Module::factory()->create(['slug' => 'projects', 'name' => 'Projects']);
        $inventory = Module::factory()->sellableAsAddon()->create(['slug' => 'inventory', 'name' => 'Inventory']);

        $inventory->prices()->createMany([
            ['billing_period' => BillingPeriod::Monthly(), 'price' => 15, 'currency' => 'USD'],
            ['billing_period' => BillingPeriod::Annual(), 'price' => 150, 'currency' => 'USD'],
        ]);

        foreach ([$crm, $projects, $inventory] as $module) {
            foreach ([Action::Create(), Action::View(), Action::Update()] as $action) {
                $module->permissions()->create([
                    'slug' => "{$module->slug}.".strtolower((string) $action->value),
                ]);
            }
        }

        $basicReports = Feature::factory()->create(['slug' => 'basic_reports', 'name' => 'Basic Reports']);
        $advancedReports = Feature::factory()->create(['slug' => 'advanced_reports', 'name' => 'Advanced Reports']);
        $apiAccess = Feature::factory()->create(['slug' => 'api_access', 'name' => 'API Access']);

        $users = LimitType::factory()->create(['key' => 'users', 'name' => 'Users', 'unit' => null]);
        $storage = LimitType::factory()->create(['key' => 'storage_gb', 'name' => 'Storage', 'unit' => 'GB']);

        $starter = Plan::factory()->create(['slug' => 'starter', 'name' => 'Starter', 'trial_days' => 14]);
        $starter->prices()->createMany([
            ['billing_period' => BillingPeriod::Monthly(), 'price' => 29, 'currency' => 'USD'],
            ['billing_period' => BillingPeriod::Annual(), 'price' => 290, 'currency' => 'USD'],
        ]);
        $starter->modules()->attach([$crm->id, $projects->id]);
        $starter->features()->attach($basicReports->id);
        $starter->limits()->attach([
            $users->id => ['value' => 5],
            $storage->id => ['value' => 10],
        ]);

        $pro = Plan::factory()->create(['slug' => 'pro', 'name' => 'Pro', 'trial_days' => 14]);
        $pro->prices()->createMany([
            ['billing_period' => BillingPeriod::Monthly(), 'price' => 79, 'currency' => 'USD'],
            ['billing_period' => BillingPeriod::Annual(), 'price' => 790, 'currency' => 'USD'],
        ]);
        $pro->modules()->attach([$crm->id, $projects->id, $inventory->id]);
        $pro->features()->attach([$apiAccess->id, $advancedReports->id]);
        $pro->limits()->attach([
            $users->id => ['value' => 50],
            $storage->id => ['value' => 100],
        ]);

        // Example: Pro bundles the Support module (see SupportModuleSeeder)
        // with 4 included hours per cycle and a 4h/24h business-hours SLA.
        $support = Module::where('slug', SupportLimits::ModuleSlug)->first();

        if ($support !== null) {
            $pro->modules()->attach($support->id);
            $pro->limits()->attach(
                LimitType::whereIn('key', array_keys(self::PRO_SUPPORT_LIMITS))
                    ->pluck('id', 'key')
                    ->mapWithKeys(fn (string $id, string $key) => [$id => ['value' => self::PRO_SUPPORT_LIMITS[$key]]])
                    ->all()
            );
        }
    }
}
