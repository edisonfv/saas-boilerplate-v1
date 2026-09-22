<?php

use App\Models\Tenant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CentralAclSeeder;

test('a newly created tenant is auto-provisioned with owner and member roles but no demo user', function () {
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    $tenant->run(function () {
        expect(Role::where('name', 'owner')->where('guard_name', 'web')->exists())->toBeTrue()
            ->and(Role::where('name', 'member')->where('guard_name', 'web')->exists())->toBeTrue()
            ->and(User::count())->toBe(0);
    });

    $tenant->delete();
});

test("a tenant's ACL data is physically isolated from the central ACL", function () {
    $this->seed(CentralAclSeeder::class);

    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    // The central "super-admin" role (guard "central") does not leak into the tenant's own roles table.
    $tenant->run(function () {
        expect(Role::where('name', 'super-admin')->exists())->toBeFalse()
            ->and(Role::where('name', 'owner')->where('guard_name', 'web')->exists())->toBeTrue();
    });

    // Back in the central context, the tenant's "owner"/"member" roles don't exist here either.
    expect(Role::where('name', 'owner')->exists())->toBeFalse()
        ->and(Role::where('name', 'super-admin')->where('guard_name', 'central')->exists())->toBeTrue();

    $tenant->delete();
});
