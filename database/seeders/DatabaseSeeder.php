<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's (central) database. Tenant users live in each
     * tenant's own database — see TenantDatabaseSeeder, run via
     * config('tenancy.seeder_parameters').
     */
    public function run(): void
    {
        $this->call(GeneralModuleSeeder::class);
        $this->call(CatalogSeeder::class);
        $this->call(CentralAclSeeder::class);
    }
}
