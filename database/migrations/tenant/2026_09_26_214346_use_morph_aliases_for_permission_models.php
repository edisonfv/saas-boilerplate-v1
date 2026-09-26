<?php

use App\Models\User;
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
        $this->rename([User::class => 'user']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->rename(['user' => User::class]);
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
