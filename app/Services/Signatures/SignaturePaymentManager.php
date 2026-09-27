<?php

namespace App\Services\Signatures;

use App\Enums\SignaturePaymentMethod;
use App\Enums\SignaturePaymentReview;
use App\Enums\SignaturePaymentStatus;
use App\Models\SignaturePayment;
use App\Models\SignatureRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Signatures\PaymentLinkSent;
use App\Notifications\Signatures\PaymentReviewed;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * End-customer payments of signature requests (tenant context). Payment
 * is what unlocks sending a request to the provider:
 *
 *   Pending ──(customer uploads receipt)──▶ UnderReview ──(staff confirms)──▶ Paid
 *      ▲                                        │
 *      └──────────(staff rejects, with reason)──┘
 *
 * Staff can also register a payment directly (cash, transfer seen in the
 * bank), which is approved at once.
 */
class SignaturePaymentManager
{
    public function __construct(
        private SignatureRequestManager $requests,
        private SignatureLinks $links,
    ) {}

    /**
     * The customer reports a transfer/deposit with its receipt, through
     * their signed payment link.
     */
    public function reportReceipt(
        SignatureRequest $request,
        SignaturePaymentMethod $method,
        ?string $reference,
        UploadedFile $receipt,
    ): SignaturePayment {
        return DB::transaction(function () use ($request, $method, $reference, $receipt) {
            $locked = SignatureRequest::query()->lockForUpdate()->whereKey($request->getKey())->firstOrFail();

            if (! $locked->payment_status->equals(SignaturePaymentStatus::Pending())) {
                throw new DomainException('Esta solicitud ya tiene un comprobante en revisión o está pagada.');
            }

            $payment = $locked->payments()->create([
                ...$this->storeReceipt($locked, $receipt),
                'method' => $method,
                'amount' => $locked->sale_price ?? 0,
                'reference' => $reference,
                'review' => SignaturePaymentReview::Pending(),
                'reported_by_name' => $locked->applicantName(),
            ]);

            $this->setStatus($request, $locked, SignaturePaymentStatus::UnderReview());
            $this->requests->record($locked, "Comprobante de pago recibido ({$method->label})", $locked->applicantName());

            return $payment;
        });
    }

    /**
     * Staff registers a payment it has verified (cash at the office, a
     * transfer seen in the bank account...). Approved at once.
     */
    public function registerPayment(
        SignatureRequest $request,
        SignaturePaymentMethod $method,
        string|float $amount,
        ?string $reference,
        ?UploadedFile $receipt,
        User $staff,
    ): SignaturePayment {
        if ($request->isPaid()) {
            throw new DomainException('La solicitud ya está pagada.');
        }

        return DB::transaction(function () use ($request, $method, $amount, $reference, $receipt, $staff) {
            // A receipt the customer uploaded is superseded by the staff record.
            $request->payments()
                ->where('review', SignaturePaymentReview::Pending()->value)
                ->update(['review' => SignaturePaymentReview::Rejected()->value, 'rejection_reason' => 'Reemplazado por el pago registrado en el punto de venta.']);

            $payment = $request->payments()->create([
                ...($receipt !== null ? $this->storeReceipt($request, $receipt) : []),
                'method' => $method,
                'amount' => $amount,
                'reference' => $reference,
                'review' => SignaturePaymentReview::Approved(),
                'reported_by_name' => $staff->name,
                'reviewed_by' => (string) $staff->getKey(),
                'reviewed_by_name' => $staff->name,
                'reviewed_at' => now(),
            ]);

            $this->setStatus($request, $request, SignaturePaymentStatus::Paid());
            $this->requests->record($request, "Pago registrado: {$method->label} por \${$payment->amount}", $staff->name);

            return $payment;
        });
    }

    public function approve(SignaturePayment $payment, User $staff): void
    {
        $request = $this->review($payment, SignaturePaymentReview::Approved(), $staff);

        $this->requests->record($request, 'Pago confirmado', $staff->name);

        $this->notifyCustomer($request, new PaymentReviewed(
            $this->companyName(),
            $request->first_names,
            $request->code(),
            approved: true,
        ));
    }

    public function reject(SignaturePayment $payment, string $reason, User $staff): void
    {
        $request = $this->review($payment, SignaturePaymentReview::Rejected(), $staff, $reason);

        $this->requests->record($request, "Comprobante rechazado: {$reason}", $staff->name);

        $this->notifyCustomer($request, new PaymentReviewed(
            $this->companyName(),
            $request->first_names,
            $request->code(),
            approved: false,
            reason: $reason,
            link: $this->links->payment($request),
        ));
    }

    /**
     * Email the customer their payment link (after applying online, or
     * when staff re-sends it).
     */
    public function sendPaymentLink(SignatureRequest $request): void
    {
        if (! $request->payment_status->equals(SignaturePaymentStatus::Pending())) {
            throw new DomainException('Solo se envía el enlace de pago mientras la solicitud está pendiente de pago.');
        }

        $this->notifyCustomer($request, new PaymentLinkSent(
            $this->companyName(),
            $request->first_names,
            $request->code(),
            $request->product_name,
            $request->sale_price,
            $this->links->payment($request),
        ));
    }

    private function review(SignaturePayment $payment, SignaturePaymentReview $outcome, User $staff, ?string $reason = null): SignatureRequest
    {
        return DB::transaction(function () use ($payment, $outcome, $staff, $reason) {
            $locked = SignaturePayment::query()->lockForUpdate()->whereKey($payment->getKey())->firstOrFail();

            if (! $locked->review->equals(SignaturePaymentReview::Pending())) {
                throw new DomainException('Este pago ya fue revisado.');
            }

            $locked->update([
                'review' => $outcome,
                'rejection_reason' => $reason,
                'reviewed_by' => (string) $staff->getKey(),
                'reviewed_by_name' => $staff->name,
                'reviewed_at' => now(),
            ]);

            /** @var SignatureRequest $request */
            $request = $locked->request()->firstOrFail();
            $this->setStatus($request, $request, $outcome->equals(SignaturePaymentReview::Approved())
                ? SignaturePaymentStatus::Paid()
                : SignaturePaymentStatus::Pending());

            $payment->setRawAttributes($locked->getAttributes(), true);

            return $request;
        });
    }

    /**
     * @return array{receipt_disk: string, receipt_path: string, receipt_name: string}
     */
    private function storeReceipt(SignatureRequest $request, UploadedFile $receipt): array
    {
        $disk = (string) config('signatures.documents_disk', 'local');

        return [
            'receipt_disk' => $disk,
            'receipt_path' => (string) $receipt->storeAs(
                "signature-requests/{$request->getKey()}/payments",
                'comprobante-'.now()->format('YmdHis').'.'.$receipt->guessExtension(),
                $disk,
            ),
            'receipt_name' => $receipt->getClientOriginalName(),
        ];
    }

    private function setStatus(SignatureRequest $original, SignatureRequest $locked, SignaturePaymentStatus $status): void
    {
        $locked->forceFill(['payment_status' => $status])->save();
        $original->payment_status = $status;
        $original->syncOriginalAttribute('payment_status');
    }

    private function notifyCustomer(SignatureRequest $request, object $notification): void
    {
        Notification::route('mail', $request->email)->notify($notification);
    }

    private function companyName(): string
    {
        /** @var Tenant $tenant */
        $tenant = tenant();

        return $tenant->company_name ?? (string) $tenant->getTenantKey();
    }
}
