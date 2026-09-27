<?php

namespace App\Services\Signatures;

use App\Enums\SignatureDocumentKind;
use App\Enums\SignatureRequestStatus;
use App\Models\SignatureProduct;
use App\Models\SignatureProviderRequest;
use App\Models\SignatureRequest;
use App\Models\Tenant;
use DomainException;

/**
 * Sells a signature: the one use case that spans both databases. Only
 * paid requests (see SignaturePaymentManager) can be sold.
 *
 *   1. (tenant)  claim the draft (Draft → Submitted, compare-and-set, so a
 *                double click can't sell it twice) and read its documents;
 *   2. (central) consume one unit / the unit price from the tenant's quota
 *                (SignatureWallet, row-locked) and index the sale;
 *   3. (provider) submit the application;
 *   4. on failure, refund the consumption and put the draft back, so the
 *      tenant is never charged for a request the provider didn't accept.
 *
 * Central and tenant are separate connections, so this is a compensating
 * sequence rather than one transaction.
 */
class SignatureIssuance
{
    public function __construct(
        private SignatureWallet $wallet,
        private Contracts\SignatureProvider $provider,
        private SignatureRequestManager $requests,
    ) {}

    /**
     * @throws DomainException when the request isn't ready or the quota is exhausted
     * @throws SignatureProviderException when the provider refuses the request
     */
    public function submit(Tenant $tenant, SignatureRequest $request, ?string $actorName = null): void
    {
        $this->requests->ensureEditable($request);

        // The customer must have paid the tenant before its quota is spent.
        if (! $request->isPaid()) {
            throw new DomainException('La solicitud aún no está pagada. Confirma el pago del cliente antes de enviarla.');
        }

        $missing = $request->missingDocuments();

        if ($missing !== []) {
            throw new DomainException('Faltan documentos: '.implode(', ', array_map(
                fn (SignatureDocumentKind $kind) => $kind->label,
                $missing,
            )).'.');
        }

        $product = SignatureProduct::query()->active()->find($request->signature_product_id)
            ?? throw new DomainException('El producto de la solicitud ya no está disponible. Edita la solicitud y elige otro.');

        $account = $tenant->signatureAccount()->first();
        $this->wallet->assertCanSell($account, $product);

        $application = SignatureApplication::fromRequest($request);

        if (! $this->claim($request)) {
            throw new DomainException('La solicitud ya fue enviada.');
        }

        try {
            $consumption = $this->wallet->consume($account, $product, $request->code());
        } catch (DomainException $exception) {
            $this->release($request);

            throw $exception;
        }

        $sale = SignatureProviderRequest::create([
            'signature_account_id' => $account->id,
            'tenant_id' => $tenant->getTenantKey(),
            'tenant_request_id' => $request->id,
            'signature_product_id' => $product->id,
            'consumption_entry_id' => $consumption->id,
            'unit_cost' => $product->provider_cost,
            'unit_price' => $this->wallet->unitPrice($account, $product),
            'sale_price' => $request->sale_price,
            'provider' => $this->provider->key(),
            'status' => SignatureRequestStatus::Submitted(),
        ]);

        try {
            $token = $this->provider->submit($application);
        } catch (SignatureProviderException $exception) {
            $this->wallet->refund($consumption, 'Reverso automático: la entidad certificadora no aceptó el envío');
            $sale->delete();
            $this->release($request, $exception->getMessage());
            $this->requests->record($request, 'Envío fallido: '.$exception->getMessage(), $actorName);

            throw $exception;
        }

        $sale->update(['provider_token' => $token, 'submitted_at' => now(), 'last_event_at' => now()]);

        $request->forceFill([
            'provider_token' => $token,
            'central_reference' => $sale->id,
            'submitted_at' => now(),
            'last_error' => null,
        ])->save();

        $this->requests->record($request, 'Solicitud enviada a la entidad certificadora', $actorName);
    }

    private function claim(SignatureRequest $request): bool
    {
        $claimed = SignatureRequest::query()
            ->whereKey($request->getKey())
            ->where('status', SignatureRequestStatus::Draft()->value)
            ->update(['status' => SignatureRequestStatus::Submitted()->value]);

        $request->status = SignatureRequestStatus::Submitted();
        $request->syncOriginalAttribute('status');

        return $claimed === 1;
    }

    private function release(SignatureRequest $request, ?string $error = null): void
    {
        $request->forceFill(['status' => SignatureRequestStatus::Draft(), 'last_error' => $error])->save();
    }
}
