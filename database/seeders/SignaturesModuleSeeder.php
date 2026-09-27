<?php

namespace Database\Seeders;

use App\Enums\BillingPeriod;
use App\Enums\SignatureContainer;
use App\Enums\SignatureValidity;
use App\Models\Module;
use App\Models\ModulePermission;
use App\Models\SignatureProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Signatures\Permissions\SignaturesPermissions;

/**
 * Registers the sellable "signatures" module (electronic-signature resale)
 * in the central catalog with its tenant permission blueprint, plus an
 * example signature catalog: products with their credit unit price and
 * prepaid packages at a special price. Idempotent.
 */
class SignaturesModuleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * validity, container, credit unit price, suggested retail price,
     * prepaid packages [quantity => price].
     *
     * @var list<array{0: string, 1: string, 2: int, 3: int, 4: array<int, int>}>
     */
    private const Products = [
        ['ThirtyDays', 'File', 8, 15, []],
        ['OneYear', 'File', 15, 25, [10 => 130, 25 => 300]],
        ['TwoYears', 'File', 25, 40, [10 => 220]],
        ['ThreeYears', 'File', 33, 55, []],
        ['FiveYears', 'File', 50, 80, []],
        ['OneYear', 'Cloud', 18, 30, [10 => 160]],
    ];

    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['slug' => config('signatures.module_slug', 'signatures')],
            ['name' => 'Firmas electrónicas', 'is_active' => true, 'sellable_as_addon' => true],
        );

        if ($module->prices()->doesntExist()) {
            $module->prices()->createMany([
                ['billing_period' => BillingPeriod::Monthly(), 'price' => 30, 'currency' => 'USD'],
                ['billing_period' => BillingPeriod::Annual(), 'price' => 300, 'currency' => 'USD'],
            ]);
        }

        foreach (SignaturesPermissions::resources() as $resource => $definition) {
            foreach ($definition['actions'] as $action) {
                ModulePermission::updateOrCreate(
                    ['module_id' => $module->id, 'slug' => "tenant.{$resource}.".Str::lower((string) $action->value)],
                    ['label' => "{$action->label} ".Str::lower($definition['label'])],
                );
            }

            foreach ($definition['special'] ?? [] as $key => $label) {
                ModulePermission::updateOrCreate(
                    ['module_id' => $module->id, 'slug' => "tenant.{$resource}.{$key}"],
                    ['label' => $label],
                );
            }
        }

        if (SignatureProduct::query()->exists()) {
            return;
        }

        foreach (self::Products as [$validity, $container, $creditPrice, $retailPrice, $packages]) {
            $validity = SignatureValidity::from($validity);
            $container = SignatureContainer::from($container);

            $product = SignatureProduct::create([
                'name' => "Firma {$validity->label}".($container->equals(SignatureContainer::Cloud()) ? ' en la nube' : ''),
                'validity' => $validity,
                'container' => $container,
                'credit_unit_price' => $creditPrice,
                'suggested_retail_price' => $retailPrice,
            ]);

            foreach ($packages as $quantity => $price) {
                $product->packages()->create([
                    'name' => "{$quantity} firmas de {$validity->label}",
                    'quantity' => $quantity,
                    'price' => $price,
                ]);
            }
        }
    }
}
