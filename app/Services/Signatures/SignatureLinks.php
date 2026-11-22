<?php

namespace App\Services\Signatures;

use App\Models\SignatureInvitation;
use App\Models\SignatureRequest;
use Illuminate\Support\Facades\URL;

/**
 * Signed links a tenant's customers use without an account, built in
 * tenant context so they point at the tenant's own domain. Signed
 * relative (validated with `signed:relative`) like the Support module's
 * links, so they don't depend on the scheme/host the app sees behind a
 * proxy. Their "single use" is enforced by state, not by the signature:
 * the payment page only accepts a receipt while the request is pending,
 * and an invitation only while not consumed.
 */
class SignatureLinks
{
    public const PaymentLinkDays = 7;

    public const InvitationDays = 30;

    public function payment(SignatureRequest $request): string
    {
        return url(URL::temporarySignedRoute(
            'tenant.signatures.storefront.payment.show',
            now()->addDays(self::PaymentLinkDays),
            ['signatureRequest' => $request->getKey()],
            absolute: false,
        ));
    }

    public function invitation(SignatureInvitation $invitation): string
    {
        return url(URL::temporarySignedRoute(
            'tenant.signatures.storefront.invitation.show',
            $invitation->expires_at,
            ['invitation' => $invitation->getKey()],
            absolute: false,
        ));
    }

    /**
     * wa.me link to message a customer's Ecuadorian mobile (09XXXXXXXX)
     * with a pre-filled text, or null if the number can't be converted.
     */
    public function whatsappTo(?string $mobile, string $message): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $mobile) ?? '';

        $international = match (true) {
            str_starts_with($digits, '593') => $digits,
            preg_match('/^09\d{8}$/', $digits) === 1 => '593'.substr($digits, 1),
            default => null,
        };

        return $international === null ? null : "https://wa.me/{$international}?text=".rawurlencode($message);
    }
}
