<?php

namespace Modules\Signatures\Http\Controllers;

use App\Enums\SignaturePaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\SignatureInvitation;
use App\Models\SignatureProduct;
use App\Models\SignatureStorefront;
use App\Models\User;
use App\Services\Signatures\SignatureInvitationManager;
use App\Services\Signatures\SignatureLinks;
use App\Services\Signatures\SignaturePresenter;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Signatures\Http\Requests\StoreSignatureInvitationRequest;

/**
 * Prepaid single-use application links: staff collects the payment first
 * and sends the customer a link (by email and/or WhatsApp).
 */
class SignatureInvitationController extends Controller
{
    public function __construct(
        private SignatureInvitationManager $invitations,
        private SignatureLinks $links,
    ) {}

    public function index(SignaturePresenter $presenter): Response
    {
        $storefront = SignatureStorefront::current();
        $products = SignatureProduct::query()->active()->orderBy('credit_unit_price')->get();

        return Inertia::render('Signatures/Invitations/Index', [
            'invitations' => SignatureInvitation::query()->with('request')->latest()->paginate(15)
                ->through(fn (SignatureInvitation $invitation) => [
                    'id' => $invitation->id,
                    'customer_name' => $invitation->customer_name,
                    'customer_email' => $invitation->customer_email,
                    'product_name' => $invitation->product_name,
                    'amount' => $invitation->amount,
                    'expires_at' => $invitation->expires_at,
                    'consumed_at' => $invitation->consumed_at,
                    'is_usable' => $invitation->isUsable(),
                    'request_id' => $invitation->signature_request_id,
                    'request_code' => $invitation->request?->code(),
                    'created_by_name' => $invitation->created_by_name,
                    'link' => $invitation->isUsable() ? $this->links->invitation($invitation) : null,
                    'whatsapp_url' => $invitation->isUsable() ? $this->links->whatsappTo(
                        $invitation->customer_phone,
                        "Hola {$invitation->customer_name}, completa tu solicitud de firma electrónica aquí: {$this->links->invitation($invitation)}",
                    ) : null,
                ]),
            'products' => $products->map(fn (SignatureProduct $product) => [
                ...$presenter->product($product),
                'retail_price' => $product->retailPriceFrom($storefront->priceFor($product->id)),
            ])->values(),
            'methods' => SignaturePaymentMethod::toArray(),
            'validDays' => SignatureLinks::InvitationDays,
        ]);
    }

    public function store(StoreSignatureInvitationRequest $request): RedirectResponse
    {
        /** @var User $staff */
        $staff = $request->user();

        $this->invitations->create(
            SignatureProduct::query()->findOrFail($request->string('signature_product_id')->toString()),
            $request->string('customer_name')->toString(),
            $request->input('customer_email'),
            $request->input('customer_phone'),
            $request->input('amount'),
            SignaturePaymentMethod::from($request->string('method')->toString()),
            $request->input('reference'),
            $staff,
        );

        return back()->with('status', 'signature-invitation-created');
    }

    public function resend(SignatureInvitation $invitation): RedirectResponse
    {
        try {
            $this->invitations->send($invitation);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['invitation' => $exception->getMessage()]);
        }

        return back()->with('status', 'signature-invitation-sent');
    }
}
