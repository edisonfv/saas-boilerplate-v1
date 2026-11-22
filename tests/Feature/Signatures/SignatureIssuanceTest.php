<?php

use App\Enums\SignaturePaymentStatus;
use App\Enums\SignatureRequestSource;
use App\Enums\SignatureRequestStatus;
use App\Exceptions\SignatureQuotaExceeded;
use App\Models\SignatureProviderRequest;
use App\Models\SignatureRequest;
use App\Services\Signatures\SignatureIssuance;
use App\Services\Signatures\SignatureProviderException;
use App\Services\Signatures\SignatureRequestManager;
use App\Services\Signatures\SignatureWallet;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    seedSignatures();
});

afterEach(function () {
    tenancy()->end();
});

function submitDraft($tenant, SignatureRequest $draft): SignatureRequest
{
    return $tenant->run(function () use ($tenant, $draft) {
        $request = SignatureRequest::with('documents')->findOrFail($draft->id);
        app(SignatureIssuance::class)->submit($tenant, $request, 'Operador');

        return $request->fresh(['events']);
    });
}

test('submitting a draft consumes quota and sends the application to Uanataca', function () {
    Http::fake(['uanataca.test/*' => Http::response(['result' => true, 'message' => 'OK', 'token' => 'TKN-123'])]);

    [$tenant] = signatureTenant('Credit', creditLimit: 100);
    $draft = signatureDraft($tenant);

    $submitted = submitDraft($tenant, $draft);

    expect($submitted->status->equals(SignatureRequestStatus::Submitted()))->toBeTrue()
        ->and($submitted->provider_token)->toBe('TKN-123')
        ->and($submitted->events->pluck('description'))->toContain('Solicitud enviada a la entidad certificadora')
        ->and($tenant->signatureAccount->fresh()->credit_used)->toBe('15.00');

    $sale = SignatureProviderRequest::query()->sole();
    expect($sale->tenant_request_id)->toBe($draft->id)
        ->and($sale->provider_token)->toBe('TKN-123')
        ->and($sale->consumption)->not->toBeNull();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://uanataca.test/v4/solicitud'
        && $request->hasHeader('Authorization', 'Bearer provider-token')
        && $request['tipo_solicitud'] === '1'
        && $request['contenedor'] === '0'
        && $request['nombres'] === 'MARÍA JOSÉ'
        && $request['tipodocumento'] === 'CEDULA'
        && $request['numerodocumento'] === '1710034065'
        && $request['sexo'] === 'MUJER'
        && $request['fecha_nacimiento'] === '1990/05/10'
        && $request['vigenciafirma'] === '1 año'
        && base64_decode($request['f_selfie'], true) !== false
        && ! isset($request['apikey']));
});

test('a natural person with RUC is sent as a natural person with its RUC and RUC copy', function () {
    Http::fake(['uanataca.test/*' => Http::response(['result' => true, 'token' => 'TKN-RUC'])]);

    [$tenant] = signatureTenant('Credit', creditLimit: 100);
    $draft = $tenant->run(fn () => app(SignatureRequestManager::class)->create(
        signatureProduct(),
        signatureApplicant(['applicant_type' => 'NaturalPersonWithRuc', 'personal_ruc' => '1710034065001']),
        [...signatureDocuments(), 'RucCopy' => UploadedFile::fake()->createWithContent('ruc.pdf', '%PDF-1.4 certificado RUC')],
        SignatureRequestSource::Workspace(),
        paymentStatus: SignaturePaymentStatus::Paid(),
    ));

    submitDraft($tenant, $draft);

    Http::assertSent(fn (Request $request) => $request['tipo_solicitud'] === '1'
        && $request['ruc_personal'] === '1710034065001'
        && isset($request['f_copiaruc'])
        && ! isset($request['empresa']));
});

test('without a bearer token the legacy apikey and uid go in the body', function () {
    config(['services.uanataca.token' => null, 'services.uanataca.api_key' => 'key', 'services.uanataca.uid' => '42']);
    Http::fake(['uanataca.test/*' => Http::response(['result' => true, 'tokenSolicitud' => 'TKN-9'])]);

    [$tenant] = signatureTenant('Credit', creditLimit: 100);

    submitDraft($tenant, signatureDraft($tenant));

    Http::assertSent(fn (Request $request) => $request['apikey'] === 'key'
        && $request['uid'] === '42'
        && ! $request->hasHeader('Authorization'));
});

test('a provider refusal refunds the quota and keeps the draft with the error', function () {
    Http::fake(['uanataca.test/*' => Http::response(['result' => false, 'message' => 'Cédula no válida'], 400)]);

    [$tenant] = signatureTenant('Prepaid');
    app(SignatureWallet::class)->adjustUnits($tenant->signatureAccount, signatureProduct(), 1, 'Cortesía');
    $draft = signatureDraft($tenant);

    expect(fn () => submitDraft($tenant, $draft))->toThrow(SignatureProviderException::class, 'Cédula no válida');

    $tenant->run(function () use ($draft) {
        $request = SignatureRequest::findOrFail($draft->id);

        expect($request->status->equals(SignatureRequestStatus::Draft()))->toBeTrue()
            ->and($request->last_error)->toContain('Cédula no válida');
    });

    expect(app(SignatureWallet::class)->sellableUnits($tenant->signatureAccount, signatureProduct()))->toBe(1)
        ->and(SignatureProviderRequest::query()->count())->toBe(0);
});

test('a draft with missing documents is not sent nor charged', function () {
    Http::fake();

    [$tenant] = signatureTenant('Credit', creditLimit: 100);
    $draft = signatureDraft($tenant, documents: []);

    expect(fn () => submitDraft($tenant, $draft))->toThrow(DomainException::class, 'Faltan documentos');

    Http::assertNothingSent();
    expect($tenant->signatureAccount->fresh()->credit_used)->toBe('0.00');
});

test('without quota nothing is sent', function () {
    Http::fake();

    [$tenant] = signatureTenant('Prepaid');

    expect(fn () => submitDraft($tenant, signatureDraft($tenant)))->toThrow(SignatureQuotaExceeded::class);

    Http::assertNothingSent();
});

test('a request is sold only once', function () {
    Http::fake(['uanataca.test/*' => Http::response(['result' => true, 'token' => 'TKN-1'])]);

    [$tenant] = signatureTenant('Credit', creditLimit: 100);
    $draft = signatureDraft($tenant);
    submitDraft($tenant, $draft);

    expect(fn () => submitDraft($tenant, $draft))->toThrow(DomainException::class, 'ya fue enviada');
    expect($tenant->signatureAccount->fresh()->credit_used)->toBe('15.00');
});
