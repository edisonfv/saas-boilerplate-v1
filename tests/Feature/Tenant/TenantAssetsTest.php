<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    $this->buildDirectory = 'build-test-'.uniqid();
    $manifest = [];

    foreach (['resources/css/app.css', 'resources/js/app.ts', 'resources/js/pages/General/Auth/Login.vue'] as $entry) {
        $manifest[$entry] = ['file' => 'assets/'.basename($entry).'.js', 'src' => $entry, 'isEntry' => true];
    }

    File::ensureDirectoryExists(public_path($this->buildDirectory));
    File::put(public_path($this->buildDirectory.'/manifest.json'), json_encode($manifest));

    Vite::useBuildDirectory($this->buildDirectory)->useHotFile(storage_path('framework/no-vite-hot-file'));
});

afterEach(function () {
    File::deleteDirectory(public_path($this->buildDirectory));
    tenancy()->end();
});

test('tenant pages load the compiled frontend from the public build, not from tenant storage', function () {
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid(), 'company_name' => 'Acme S.A.']);
    $domain = $tenant->id.'.assets-test.local';
    $tenant->createDomain($domain);

    $this->get("http://{$domain}/login")
        ->assertOk()
        ->assertSee("/{$this->buildDirectory}/assets/app.ts.js", false)
        ->assertDontSee('tenancy/assets', false);
});

test('tenant files are still served through the tenant asset route', function () {
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid(), 'company_name' => 'Acme S.A.']);

    $url = $tenant->run(fn () => tenant_asset('storefront/banner/photo.jpg'));

    expect($url)->toContain('/tenancy/assets/storefront/banner/photo.jpg');
});
