<?php

namespace Database\Seeders;

use App\Enums\Action;
use App\Enums\BillingPeriod;
use App\Models\Feature;
use App\Models\LimitType;
use App\Models\Module;
use App\Models\ModulePrice;
use App\Models\Plan;
use App\Models\PlanLimitPivot;
use App\Models\PlanPrice;
use App\Services\Support\SupportLimits;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     *
     * Idempotent: records are matched on their natural key (slug/key, plus
     * billing period for prices) and created only when missing, and pivots
     * only gain the rows they lack — re-running never duplicates anything
     * nor overwrites what an admin edited afterwards.
     */
    public function run(): void
    {
        $crm = Module::firstOrCreate(['slug' => 'crm'], ['name' => 'CRM', 'is_active' => true, 'sellable_as_addon' => false]);
        $projects = Module::firstOrCreate(['slug' => 'projects'], ['name' => 'Projects', 'is_active' => true, 'sellable_as_addon' => false]);
        $inventory = Module::firstOrCreate(['slug' => 'inventory'], ['name' => 'Inventory', 'is_active' => true, 'sellable_as_addon' => true]);

        $this->prices($inventory->prices(), [[BillingPeriod::Monthly(), 15], [BillingPeriod::Annual(), 150]]);

        foreach ([$crm, $projects, $inventory] as $module) {
            foreach ([Action::Create(), Action::View(), Action::Update()] as $action) {
                $module->permissions()->firstOrCreate([
                    'slug' => "{$module->slug}.".strtolower((string) $action->value),
                ]);
            }
        }

        $basicReports = $this->feature('basic_reports', 'Basic Reports');
        $advancedReports = $this->feature('advanced_reports', 'Advanced Reports');
        $apiAccess = $this->feature('api_access', 'API Access');

        $users = LimitType::firstOrCreate(['key' => 'users'], ['name' => 'Users', 'unit' => null]);
        $storage = LimitType::firstOrCreate(['key' => 'storage_gb'], ['name' => 'Storage', 'unit' => 'GB']);

        $starter = Plan::firstOrCreate(['slug' => 'starter'], ['name' => 'Starter', 'is_active' => true, 'trial_days' => 14]);
        $this->prices($starter->prices(), [[BillingPeriod::Monthly(), 29], [BillingPeriod::Annual(), 290]]);
        $starter->modules()->syncWithoutDetaching([$crm->id, $projects->id]);
        $starter->features()->syncWithoutDetaching([$basicReports->id]);
        $this->attachMissing($starter->limits(), [
            $users->id => ['value' => 5],
            $storage->id => ['value' => 10],
        ]);

        $pro = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'is_active' => true, 'trial_days' => 14]);
        $this->prices($pro->prices(), [[BillingPeriod::Monthly(), 79], [BillingPeriod::Annual(), 790]]);
        $pro->modules()->syncWithoutDetaching([$crm->id, $projects->id, $inventory->id]);
        $pro->features()->syncWithoutDetaching([$apiAccess->id, $advancedReports->id]);
        $this->attachMissing($pro->limits(), [
            $users->id => ['value' => 50],
            $storage->id => ['value' => 100],
        ]);

        // Example: Pro bundles the Support module (see SupportModuleSeeder)
        // with 4 included hours per cycle and a 4h/24h business-hours SLA.
        $support = Module::where('slug', SupportLimits::ModuleSlug)->first();

        if ($support !== null) {
            $pro->modules()->syncWithoutDetaching([$support->id]);
            $this->attachMissing(
                $pro->limits(),
                LimitType::whereIn('key', array_keys(self::PRO_SUPPORT_LIMITS))
                    ->pluck('id', 'key')
                    ->mapWithKeys(fn (string $id, string $key) => [$id => ['value' => self::PRO_SUPPORT_LIMITS[$key]]])
                    ->all()
            );
        }

        // Example: Pro also bundles electronic-signature resale (see SignaturesModuleSeeder).
        $signatures = Module::where('slug', config('signatures.module_slug', 'signatures'))->first();

        if ($signatures !== null) {
            $pro->modules()->syncWithoutDetaching([$signatures->id]);
        }
    }

    private function feature(string $slug, string $name): Feature
    {
        return Feature::firstOrCreate(['slug' => $slug], ['module_id' => null, 'name' => $name, 'is_active' => true]);
    }

    /**
     * Creates the missing prices (per billing period) of a plan or module.
     *
     * @param  HasMany<PlanPrice, Plan>|HasMany<ModulePrice, Module>  $prices
     * @param  list<array{BillingPeriod, int}>  $amounts  [billing period, price] pairs
     */
    private function prices(HasMany $prices, array $amounts): void
    {
        foreach ($amounts as [$billingPeriod, $amount]) {
            $prices->firstOrCreate(
                ['billing_period' => $billingPeriod->value],
                ['price' => $amount, 'currency' => 'USD'],
            );
        }
    }

    /**
     * Attaches only the related ids the pivot lacks, keeping the pivot values
     * of the ones already there (syncWithoutDetaching() would overwrite them).
     *
     * @param  BelongsToMany<LimitType, Plan, PlanLimitPivot, 'pivot'>  $relation
     * @param  array<string, array{value: int}>  $records
     */
    private function attachMissing(BelongsToMany $relation, array $records): void
    {
        $existing = $relation->pluck($relation->getRelated()->getQualifiedKeyName())->all();
        $missing = array_diff_key($records, array_flip($existing));

        if ($missing !== []) {
            $relation->attach($missing);
        }
    }
}
