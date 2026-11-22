<?php

use App\Enums\SignatureRequestStatus;
use App\Models\SignatureProviderRequest;
use App\Models\SignatureRequest;
use App\Models\SignatureWebhookEvent;
use App\Services\Signatures\SignatureIssuance;
use App\Services\Signatures\SignatureWallet;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    seedSignatures();
    Http::fake(['uanataca.test/*' => Http::response(['result' => true, 'token' => 'TKN-777'])]);

    [$this->tenant] = signatureTenant('Prepaid');
    app(SignatureWallet::class)->adjustUnits($this->tenant->signatureAccount, signatureProduct(), 1, 'Cortesía');

    $draft = signatureDraft($this->tenant);
    $this->requestId = $draft->id;

    $this->tenant->run(fn () => app(SignatureIssuance::class)->submit(
        $this->tenant,
        SignatureRequest::with('documents')->findOrFail($draft->id),
    ));
});

afterEach(function () {
    tenancy()->end();
});

function uanatacaWebhook(string $event, string $requestStatus, string $certificateStatus = ''): array
{
    return [
        'event_type' => $event,
        'sent_at' => '20260927101500',
        'request' => ['status' => $requestStatus, 'profile' => 'PF', 'scratchcard' => '1', 'pk' => '1', 'request_approvedDate' => ''],
        'certificates' => [
            'status' => $certificateStatus,
            'serial_number' => $certificateStatus !== '' ? '7A3F09' : '',
            'valid_from' => $certificateStatus !== '' ? '20260927 10:15:00' : '',
            'valid_to' => $certificateStatus !== '' ? '20270927 10:15:00' : '',
        ],
        'tokenSolicitud' => 'TKN-777',
    ];
}

function tenantRequest($tenant, string $id): SignatureRequest
{
    return $tenant->run(fn () => SignatureRequest::with('events')->findOrFail($id));
}

test('webhooks without the shared bearer token are rejected', function () {
    $this->postJson(route('api.signatures.webhooks.uanataca'), uanatacaWebhook('REQUEST_VALIDATION', 'VALIDATION'))
        ->assertUnauthorized();

    $this->withToken('wrong')
        ->postJson(route('api.signatures.webhooks.uanataca'), uanatacaWebhook('REQUEST_VALIDATION', 'VALIDATION'))
        ->assertUnauthorized();

    expect(SignatureWebhookEvent::query()->count())->toBe(0);
});

test('an issued certificate reaches the tenant request, once', function () {
    $payload = uanatacaWebhook('REQUEST_ENROLLED', 'ISSUED', 'VALID');

    $this->withToken('webhook-secret')
        ->postJson(route('api.signatures.webhooks.uanataca'), $payload)
        ->assertOk()
        ->assertJson(['result' => true, 'processed' => true]);

    $this->withToken('webhook-secret')
        ->postJson(route('api.signatures.webhooks.uanataca'), $payload)
        ->assertOk();

    $request = tenantRequest($this->tenant, $this->requestId);
    // Date casts need the model's (tenant) connection, so read them inside it.
    $validTo = $this->tenant->run(fn () => $request->certificate_valid_to->toDateString());

    expect($request->status->equals(SignatureRequestStatus::Issued()))->toBeTrue()
        ->and($request->certificate_serial)->toBe('7A3F09')
        ->and($validTo)->toBe('2027-09-27')
        ->and($request->events->where('description', 'Firma electrónica emitida'))->toHaveCount(1)
        ->and(SignatureWebhookEvent::query()->count())->toBe(1)
        ->and(SignatureProviderRequest::query()->sole()->status->equals(SignatureRequestStatus::Issued()))->toBeTrue();
});

test('a rejected request gives the unit back to the tenant', function () {
    $wallet = app(SignatureWallet::class);
    expect($wallet->sellableUnits($this->tenant->signatureAccount, signatureProduct()))->toBe(0);

    $this->withToken('webhook-secret')
        ->postJson(route('api.signatures.webhooks.uanataca'), uanatacaWebhook('REQUEST_REJECTED', 'REJECTED'))
        ->assertOk();

    expect(tenantRequest($this->tenant, $this->requestId)->status->equals(SignatureRequestStatus::Rejected()))->toBeTrue()
        ->and($wallet->sellableUnits($this->tenant->signatureAccount, signatureProduct()))->toBe(1);
});

test('a late REQUEST_CREATED does not move the request back', function () {
    foreach (['REQUEST_VALIDATION' => 'VALIDATION', 'REQUEST_CREATED' => 'CREATED'] as $event => $status) {
        $this->withToken('webhook-secret')
            ->postJson(route('api.signatures.webhooks.uanataca'), uanatacaWebhook($event, $status))
            ->assertOk();
    }

    expect(tenantRequest($this->tenant, $this->requestId)->status->equals(SignatureRequestStatus::InValidation()))->toBeTrue();
});

test('webhooks for unknown requests are kept for replay', function () {
    $payload = [...uanatacaWebhook('REQUEST_APPROVED', 'ENROLLREADY'), 'tokenSolicitud' => 'UNKNOWN'];

    $this->withToken('webhook-secret')
        ->postJson(route('api.signatures.webhooks.uanataca'), $payload)
        ->assertOk()
        ->assertJson(['processed' => false]);

    $event = SignatureWebhookEvent::query()->sole();
    expect($event->processed_at)->toBeNull()
        ->and($event->error)->toContain('UNKNOWN');

    $this->artisan('signatures:replay-webhooks')->assertSuccessful();
});
