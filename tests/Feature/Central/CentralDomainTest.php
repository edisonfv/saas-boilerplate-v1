<?php

use App\Models\Tenant;

afterEach(function () {
    tenancy()->end();
});

test('the central console only answers on central domains', function () {
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid(), 'company_name' => 'Acme S.A.']);
    $domain = $tenant->id.'.central-domain-test.local';
    $tenant->createDomain($domain);

    $this->get("http://{$domain}/central/login")->assertNotFound();
    $this->get("http://{$domain}/central/forgot-password")->assertNotFound();
    $this->post("http://{$domain}/central/forgot-password", ['email' => 'owner@acme.test'])->assertNotFound();

    // route() would reuse the tenant host of the previous request.
    $this->get('http://'.config('tenancy.central_domains.0').'/central/login')->assertOk();
});
