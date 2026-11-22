<?php

namespace App\Services\Signatures;

use App\Models\SignatureProviderRequest;
use App\Models\SignatureRequest;
use App\Models\SignatureWebhookEvent;
use RuntimeException;
use Throwable;

/**
 * Applies a stored provider webhook: updates the central sale index,
 * returns the quota when the request ends unfulfilled (rejected or
 * cancelled, if config('signatures.refund_unfulfilled')), and mirrors the
 * status on the tenant's own request by switching into its database.
 * Runs in central context. Safe to re-run on the same event.
 */
class SignatureStatusSynchronizer
{
    public function __construct(
        private Contracts\SignatureProvider $provider,
        private SignatureWallet $wallet,
        private SignatureRequestManager $requests,
    ) {}

    /**
     * Store an incoming webhook (deduplicated by body hash) and process it.
     * Returns the stored event; processing errors are recorded on it
     * rather than thrown, so the provider isn't asked to retry a webhook
     * that can't succeed (replay later with `signatures:replay-webhooks`).
     *
     * @param  array<string, mixed>  $payload
     */
    public function receive(array $payload): SignatureWebhookEvent
    {
        $event = SignatureWebhookEvent::query()->firstOrCreate(
            ['fingerprint' => hash('sha256', (string) json_encode($payload))],
            [
                'provider' => $this->provider->key(),
                'event_type' => is_string($payload['event_type'] ?? null) ? $payload['event_type'] : null,
                'provider_token' => is_string($payload['tokenSolicitud'] ?? null) ? $payload['tokenSolicitud'] : null,
                'payload' => $payload,
            ],
        );

        if ($event->processed_at === null) {
            $this->process($event);
        }

        return $event;
    }

    public function process(SignatureWebhookEvent $event): bool
    {
        try {
            $update = $this->provider->parseWebhook($event->payload);

            $sale = SignatureProviderRequest::query()
                ->with(['tenant', 'consumption.account'])
                ->where('provider_token', $update->providerToken)
                ->first() ?? throw new RuntimeException("No hay una venta registrada con el token {$update->providerToken}.");

            $sale->update(['status' => $update->status, 'last_event_at' => now()]);

            if ($update->status->isUnfulfilled() && config('signatures.refund_unfulfilled', true) && $sale->consumption !== null) {
                $this->wallet->refund($sale->consumption, "Reverso automático: {$update->description}");
            }

            $sale->tenant->run(function () use ($sale, $update) {
                $request = SignatureRequest::query()->findOrFail($sale->tenant_request_id);

                $this->requests->applyStatusUpdate($request, $update);
            });

            $event->update(['processed_at' => now(), 'error' => null]);

            return true;
        } catch (Throwable $exception) {
            report($exception);
            $event->update(['error' => $exception->getMessage()]);

            return false;
        }
    }
}
