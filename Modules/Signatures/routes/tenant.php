<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Signatures\Http\Controllers\SignatureInvitationController;
use Modules\Signatures\Http\Controllers\SignaturePaymentController;
use Modules\Signatures\Http\Controllers\SignatureRequestController;
use Modules\Signatures\Http\Controllers\SignatureSettlementController;
use Modules\Signatures\Http\Controllers\StorefrontController;
use Modules\Signatures\Http\Controllers\StorefrontSettingsController;

/*
|--------------------------------------------------------------------------
| Signatures Module Routes (Tenant)
|--------------------------------------------------------------------------
|
| Registered with the tenancy middleware by this module's
| RouteServiceProvider. URIs must not collide with central ones (routes are
| not domain-bound): the public application flow uses /solicitud and the
| workspace /firmas-electronicas. Never "/" (see routes/tenant.php): the
| public landing page is served at "/" by App\Http\Controllers\HomeController.
|
*/

// Public website on the tenant's subdomain (guests). It keeps taking orders
// when the tenant's subscription lapsed (only a suspension closes it): the
// pending orders are what motivates the renewal.
Route::middleware(['tenant.active:allow-lapsed', 'tenant.module:signatures,allow-lapsed'])
    ->name('tenant.signatures.storefront.')
    ->group(function () {
        Route::get('/solicitud', [StorefrontController::class, 'create'])->name('create');
        Route::post('/solicitud', [StorefrontController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('store');
        Route::get('/solicitud/enviada', [StorefrontController::class, 'received'])->name('received');

        // Signed links sent to customers (see App\Services\Signatures\SignatureLinks).
        Route::middleware('signed:relative')->group(function () {
            Route::get('/solicitud/{signatureRequest}/pago', [StorefrontController::class, 'payment'])->name('payment.show');
            Route::post('/solicitud/{signatureRequest}/pago', [StorefrontController::class, 'reportPayment'])
                ->middleware('throttle:10,1')
                ->name('payment.store');

            Route::get('/solicitud/invitacion/{invitation}', [StorefrontController::class, 'invitation'])->name('invitation.show');
            Route::post('/solicitud/invitacion/{invitation}', [StorefrontController::class, 'redeemInvitation'])
                ->middleware('throttle:5,1')
                ->name('invitation.store');
        });

        // Former storefront address.
        Route::permanentRedirect('/firmas', '/');
    });

Route::middleware(['auth', 'tenant.active', 'tenant.module:signatures'])
    ->prefix('firmas-electronicas')
    ->name('tenant.signatures.')
    ->group(function () {
        Route::middleware('permission:tenant.signature-requests.create')->group(function () {
            Route::get('/solicitudes/nueva', [SignatureRequestController::class, 'create'])->name('requests.create');
            Route::post('/solicitudes', [SignatureRequestController::class, 'store'])->name('requests.store');
        });

        Route::middleware('permission:tenant.signature-requests.view')->group(function () {
            Route::get('/', [SignatureRequestController::class, 'index'])->name('requests.index');
            Route::get('/solicitudes/{signatureRequest}', [SignatureRequestController::class, 'show'])->name('requests.show');
            Route::get('/solicitudes/{signatureRequest}/documentos/{document}', [SignatureRequestController::class, 'document'])
                ->scopeBindings()
                ->name('requests.documents.show');
        });

        Route::middleware('permission:tenant.signature-requests.update')->group(function () {
            Route::get('/solicitudes/{signatureRequest}/editar', [SignatureRequestController::class, 'edit'])->name('requests.edit');
            Route::put('/solicitudes/{signatureRequest}', [SignatureRequestController::class, 'update'])->name('requests.update');
        });

        Route::delete('/solicitudes/{signatureRequest}', [SignatureRequestController::class, 'destroy'])
            ->middleware('permission:tenant.signature-requests.delete')
            ->name('requests.destroy');

        // Consumes the tenant's quota (prepaid units or credit).
        Route::post('/solicitudes/{signatureRequest}/enviar', [SignatureRequestController::class, 'submit'])
            ->middleware(['permission:tenant.signature-requests.submit', 'throttle:10,1'])
            ->name('requests.submit');

        // End-customer payments and prepaid links (unlock "enviar").
        Route::middleware('permission:tenant.signature-requests.payments')->group(function () {
            Route::post('/solicitudes/{signatureRequest}/pagos', [SignaturePaymentController::class, 'store'])->name('requests.payments.store');
            Route::post('/solicitudes/{signatureRequest}/enlace-de-pago', [SignaturePaymentController::class, 'sendLink'])
                ->middleware('throttle:10,1')
                ->name('requests.payments.link');

            Route::scopeBindings()->group(function () {
                Route::get('/solicitudes/{signatureRequest}/pagos/{payment}/comprobante', [SignaturePaymentController::class, 'receipt'])->name('requests.payments.receipt');
                Route::post('/solicitudes/{signatureRequest}/pagos/{payment}/confirmar', [SignaturePaymentController::class, 'approve'])->name('requests.payments.approve');
                Route::post('/solicitudes/{signatureRequest}/pagos/{payment}/rechazar', [SignaturePaymentController::class, 'reject'])->name('requests.payments.reject');
            });

            Route::get('/enlaces-prepagados', [SignatureInvitationController::class, 'index'])->name('invitations.index');
            Route::post('/enlaces-prepagados', [SignatureInvitationController::class, 'store'])->name('invitations.store');
            Route::post('/enlaces-prepagados/{invitation}/reenviar', [SignatureInvitationController::class, 'resend'])
                ->middleware('throttle:10,1')
                ->name('invitations.resend');
        });

        Route::middleware('permission:tenant.signature-requests.settlement')->group(function () {
            Route::get('/liquidacion', [SignatureSettlementController::class, 'index'])->name('settlement.index');
            Route::get('/liquidacion/exportar', [SignatureSettlementController::class, 'export'])->name('settlement.export');
        });

        Route::middleware('permission:tenant.signature-storefront.update')->group(function () {
            Route::get('/sitio-web', [StorefrontSettingsController::class, 'edit'])->name('storefront.edit');
            Route::put('/sitio-web', [StorefrontSettingsController::class, 'update'])->name('storefront.update');
            Route::post('/sitio-web/fotos', [StorefrontSettingsController::class, 'storeImage'])
                ->middleware('throttle:20,1')
                ->name('storefront.images.store');
        });
    });
