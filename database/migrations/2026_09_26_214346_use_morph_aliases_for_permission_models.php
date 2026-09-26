<?php

use App\Models\CentralUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * AppServiceProvider enforces a morph map, so polymorphic "*_type"
     * columns store aliases instead of class names. Convert the Spatie
     * permission pivots already written with the fully qualified class.
     */
    public function up(): void
    {
        $this->rename([CentralUser::class => 'central_user']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->rename(['central_user' => CentralUser::class]);
    }

    /**
     * @param  array<string, string>  $types
     */
    private function rename(array $types): void
    {
        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            foreach ($types as $from => $to) {
                DB::table($table)->where('model_type', $from)->update(['model_type' => $to]);
            }
        }
    }
};
