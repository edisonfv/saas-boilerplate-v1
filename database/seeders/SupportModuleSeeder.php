<?php

namespace Database\Seeders;

use App\Enums\BillingPeriod;
use App\Models\LimitType;
use App\Models\Module;
use App\Models\ModulePermission;
use App\Models\SupportAttendanceType;
use App\Models\SupportBusinessHour;
use App\Models\SupportSetting;
use App\Services\Support\SupportLimits;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Support\Permissions\SupportPermissions;

/**
 * Registers the sellable "support" module in the central catalog: its tenant
 * permission blueprint, the plan limits it reads (included hours, SLA,
 * simultaneous appointments) and sensible scheduling defaults (Mon–Fri
 * 08:00–20:00, three attendance types). Idempotent.
 */
class SupportModuleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['slug' => SupportLimits::ModuleSlug],
            ['name' => 'Soporte', 'is_active' => true, 'sellable_as_addon' => true],
        );

        if ($module->prices()->doesntExist()) {
            $module->prices()->createMany([
                ['billing_period' => BillingPeriod::Monthly(), 'price' => 20, 'currency' => 'USD'],
                ['billing_period' => BillingPeriod::Annual(), 'price' => 200, 'currency' => 'USD'],
            ]);
        }

        foreach (SupportPermissions::resources() as $resource => $definition) {
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

        $limits = [
            SupportLimits::IncludedHours => ['Horas de soporte incluidas', 'h/ciclo'],
            SupportLimits::MaxActiveAppointments => ['Citas de soporte simultáneas', null],
            SupportLimits::FirstResponseHours => ['SLA primera respuesta (prioridad normal)', 'h hábiles'],
            SupportLimits::ResolutionHours => ['SLA resolución (prioridad normal)', 'h hábiles'],
        ];

        foreach ($limits as $key => [$name, $unit]) {
            LimitType::firstOrCreate(['key' => $key], ['name' => $name, 'unit' => $unit, 'is_active' => true]);
        }

        SupportSetting::current();

        if (SupportBusinessHour::query()->doesntExist()) {
            foreach (range(1, 5) as $weekday) {
                SupportBusinessHour::create(['weekday' => $weekday, 'opens_at' => '08:00', 'closes_at' => '20:00']);
            }
        }

        if (SupportAttendanceType::query()->doesntExist()) {
            SupportAttendanceType::create(['name' => 'Consulta rápida', 'duration_minutes' => 30]);
            SupportAttendanceType::create(['name' => 'Configuración asistida', 'duration_minutes' => 45]);
            SupportAttendanceType::create(['name' => 'Capacitación', 'duration_minutes' => 60]);
        }
    }
}
