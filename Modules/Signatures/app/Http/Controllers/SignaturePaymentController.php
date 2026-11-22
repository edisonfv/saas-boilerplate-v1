<?php

namespace Modules\Signatures\Http\Controllers;

use App\Enums\SignaturePaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\SignaturePayment;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Services\Signatures\SignaturePaymentManager;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Signatures\Http\Requests\RegisterSignaturePaymentRequest;
use Modules\Signatures\Http\Requests\RejectSignaturePaymentRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Staff side of end-customer payments: register a payment, confirm or
 * reject an uploaded receipt, re-send the payment link.
 */
class SignaturePaymentController extends Controller
{
    public function __construct(private SignaturePaymentManager $payments) {}

    public function store(RegisterSignaturePaymentRequest $request, SignatureRequest $signatureRequest): RedirectResponse
    {
        $this->guard(fn () => $this->payments->registerPayment(
            $signatureRequest,
            SignaturePaymentMethod::from($request->string('method')->toString()),
            $request->input('amount'),
            $request->input('reference'),
            $request->file('receipt'),
            $this->staff($request),
        ), 'method');

        return back()->with('status', 'signature-payment-registered');
    }

    public function approve(Request $request, SignatureRequest $signatureRequest, SignaturePayment $payment): RedirectResponse
    {
        $this->guard(fn () => $this->payments->approve($payment, $this->staff($request)), 'payment');

        return back()->with('status', 'signature-payment-approved');
    }

    public function reject(RejectSignaturePaymentRequest $request, SignatureRequest $signatureRequest, SignaturePayment $payment): RedirectResponse
    {
        $this->guard(fn () => $this->payments->reject(
            $payment,
            $request->string('reason')->toString(),
            $this->staff($request),
        ), 'reason');

        return back()->with('status', 'signature-payment-rejected');
    }

    public function sendLink(SignatureRequest $signatureRequest): RedirectResponse
    {
        $this->guard(fn () => $this->payments->sendPaymentLink($signatureRequest), 'payment');

        return back()->with('status', 'signature-payment-link-sent');
    }

    public function receipt(SignatureRequest $signatureRequest, SignaturePayment $payment): StreamedResponse
    {
        abort_unless($payment->hasReceipt(), 404);

        return Storage::disk((string) $payment->receipt_disk)->download((string) $payment->receipt_path, $payment->receipt_name);
    }

    private function staff(Request $request): User
    {
        /** @var User */
        return $request->user();
    }

    private function guard(callable $action, string $field): void
    {
        try {
            $action();
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([$field => $exception->getMessage()]);
        }
    }
}
