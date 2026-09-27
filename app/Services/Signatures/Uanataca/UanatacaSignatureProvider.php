<?php

namespace App\Services\Signatures\Uanataca;

use App\Enums\SignatureRequestStatus;
use App\Services\Signatures\Contracts\SignatureProvider;
use App\Services\Signatures\ProviderStatusUpdate;
use App\Services\Signatures\SignatureApplication;
use App\Services\Signatures\SignatureProviderException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Uanataca (Ecuador) long-duration signatures API v4 adapter.
 *
 * Responses are JSON shaped `{"result": bool, "message": string, ...}`;
 * a successful solicitud carries the request token Uanataca later echoes
 * as `tokenSolicitud` in its status webhooks.
 */
class UanatacaSignatureProvider implements SignatureProvider
{
    /**
     * Webhook event_type => local status and timeline description.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const Events = [
        'REQUEST_CREATED' => ['Submitted', 'Solicitud registrada por la entidad certificadora'],
        'REQUEST_VALIDATION' => ['InValidation', 'Solicitud en validación'],
        'REQUEST_APPROVED' => ['Approved', 'Solicitud aprobada, lista para emisión'],
        'REQUEST_REJECTED' => ['Rejected', 'Solicitud rechazada por la entidad certificadora'],
        'REQUEST_UPDATE' => ['UpdateRequested', 'La entidad certificadora solicita corregir documentos'],
        'REQUEST_ENROLLED' => ['Issued', 'Firma electrónica emitida'],
        'REQUEST_CANCELED' => ['Cancelled', 'Solicitud cancelada'],
        'CERTIFICATE_ACTIVATED' => ['Issued', 'Certificado activado'],
        'CERTIFICATE_SUSPENDED' => ['Suspended', 'Certificado suspendido'],
        'CERTIFICATE_REVOKED' => ['Revoked', 'Certificado revocado'],
    ];

    public function __construct(private UanatacaPayloadMapper $mapper) {}

    public function key(): string
    {
        return 'uanataca';
    }

    public function submit(SignatureApplication $application): string
    {
        $body = $this->mapper->solicitud($application);

        if (! $this->usesBearerToken()) {
            $body += ['apikey' => (string) config('services.uanataca.api_key'), 'uid' => (string) config('services.uanataca.uid')];
        }

        try {
            $response = $this->client()->post('/v4/solicitud', $body);
        } catch (ConnectionException $exception) {
            throw new SignatureProviderException('No se pudo conectar con la entidad certificadora. Intenta nuevamente en unos minutos.', previous: $exception);
        }

        $json = (array) ($response->json() ?? []);
        $message = (string) (Arr::get($json, 'message') ?: Arr::get($json, 'mensaje') ?: 'La entidad certificadora rechazó la solicitud.');

        if ($response->failed() || Arr::get($json, 'result') === false) {
            throw new SignatureProviderException("Uanataca: {$message}", Arr::except($json, ['token', 'tokenSolicitud']));
        }

        $token = Arr::first(
            ['tokenSolicitud', 'token', 'data.tokenSolicitud', 'data.token', 'data.0.tokenSolicitud'],
            fn (string $key) => filled(Arr::get($json, $key)),
        );

        if ($token === null) {
            throw new SignatureProviderException('La entidad certificadora aceptó la solicitud pero no devolvió su identificador. Contacta a soporte antes de reenviarla.', $json);
        }

        return (string) Arr::get($json, $token);
    }

    public function verifyWebhook(Request $request): bool
    {
        $expected = (string) config('services.uanataca.webhook_token');

        return $expected !== '' && hash_equals($expected, (string) $request->bearerToken());
    }

    public function parseWebhook(array $payload): ProviderStatusUpdate
    {
        $eventType = (string) Arr::get($payload, 'event_type');
        $token = (string) Arr::get($payload, 'tokenSolicitud');

        if ($token === '' || ! array_key_exists($eventType, self::Events)) {
            throw new SignatureProviderException("Webhook de Uanataca no reconocido ({$eventType}).", $payload);
        }

        [$status, $description] = self::Events[$eventType];

        return new ProviderStatusUpdate(
            providerToken: $token,
            status: SignatureRequestStatus::from($status),
            eventType: $eventType,
            description: $description,
            certificateSerial: Arr::get($payload, 'certificates.serial_number') ?: null,
            certificateValidFrom: $this->date(Arr::get($payload, 'certificates.valid_from')),
            certificateValidTo: $this->date(Arr::get($payload, 'certificates.valid_to')),
        );
    }

    private function usesBearerToken(): bool
    {
        return filled(config('services.uanataca.token'));
    }

    private function client(): PendingRequest
    {
        $client = Http::baseUrl((string) config('services.uanataca.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.uanataca.timeout', 90));

        return $this->usesBearerToken()
            ? $client->withToken((string) config('services.uanataca.token'))
            : $client;
    }

    /**
     * Uanataca sends dates as "YYYYMMDD hh:mm:ss".
     */
    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Ymd H:i:s', trim($value)) ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
