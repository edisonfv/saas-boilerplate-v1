<?php

namespace App\Services\Signatures;

use App\Enums\SignaturePaymentMethod;
use App\Enums\SignaturePaymentReview;
use App\Enums\SignaturePaymentStatus;
use App\Enums\SignatureRequestSource;
use App\Models\SignatureInvitation;
use App\Models\SignatureProduct;
use App\Models\SignatureRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Signatures\InvitationSent;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Prepaid, single-use application links (tenant context): staff collects
 * the payment first, then sends the customer a link that creates their
 * application already paid, for the invited product. Redeeming it is
 * atomic (row lock), so a link can't be used twice.
 */
class SignatureInvitationManager
{
    public function __construct(
        private SignatureRequestManager $requests,
        private SignatureLinks $links,
    ) {}

    public function create(
        SignatureProduct $product,
        string $customerName,
        ?string $customerEmail,
        ?string $customerPhone,
        string|float $amount,
        SignaturePaymentMethod $method,
        ?string $reference,
        User $staff,
    ): SignatureInvitation {
        $invitation = DB::transaction(function () use ($product, $customerName, $customerEmail, $customerPhone, $amount, $method, $reference, $staff) {
            $invitation = SignatureInvitation::create([
                'signature_product_id' => $product->id,
                'product_name' => $product->name,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'amount' => $amount,
                'expires_at' => now()->addDays(SignatureLinks::InvitationDays),
                'created_by' => (string) $staff->getKey(),
                'created_by_name' => $staff->name,
            ]);

            $invitation->payment()->create([
                'method' => $method,
                'amount' => $amount,
                'reference' => $reference,
                'review' => SignaturePaymentReview::Approved(),
                'reported_by_name' => $staff->name,
                'reviewed_by' => (string) $staff->getKey(),
                'reviewed_by_name' => $staff->name,
                'reviewed_at' => now(),
            ]);

            return $invitation;
        });

        if ($customerEmail !== null) {
            $this->send($invitation);
        }

        return $invitation;
    }

    public function send(SignatureInvitation $invitation): void
    {
        if (! $invitation->isUsable()) {
            throw new DomainException('La invitación ya fue usada o venció.');
        }

        if ($invitation->customer_email === null) {
            throw new DomainException('La invitación no tiene correo del cliente.');
        }

        /** @var Tenant $tenant */
        $tenant = tenant();

        Notification::route('mail', $invitation->customer_email)->notify(new InvitationSent(
            $tenant->company_name ?? (string) $tenant->getTenantKey(),
            $invitation->customer_name,
            $invitation->product_name,
            $invitation->expires_at->format('d/m/Y'),
            $this->links->invitation($invitation),
        ));
    }

    /**
     * Create the customer's application from the invitation: already paid,
     * for the invited product and price. Consumes the invitation.
     *
     * @param  array<string, mixed>  $attributes  validated applicant fields
     * @param  array<string, UploadedFile>  $documents
     */
    public function redeem(SignatureInvitation $invitation, array $attributes, array $documents): SignatureRequest
    {
        return DB::transaction(function () use ($invitation, $attributes, $documents) {
            $locked = SignatureInvitation::query()->lockForUpdate()->whereKey($invitation->getKey())->firstOrFail();

            if (! $locked->isUsable()) {
                throw new DomainException('Este enlace ya fue usado o venció. Contáctanos para obtener uno nuevo.');
            }

            $product = SignatureProduct::query()->findOrFail($locked->signature_product_id);

            $request = $this->requests->create(
                $product,
                [...$attributes, 'sale_price' => $locked->amount],
                $documents,
                SignatureRequestSource::Storefront(),
                actorName: trim(($attributes['first_names'] ?? '').' '.($attributes['first_surname'] ?? '')),
                paymentStatus: SignaturePaymentStatus::Paid(),
            );

            $locked->payment()->update(['signature_request_id' => $request->getKey()]);
            $locked->update(['consumed_at' => now(), 'signature_request_id' => $request->getKey()]);

            $this->requests->record($request, 'Solicitud creada con enlace prepagado (pago registrado previamente)');

            return $request;
        });
    }
}
