<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Signatures\Http\Controllers\SignatureRequestController;
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

// Public website on the tenant's subdomain (guests).
Route::middleware(['tenant.active', 'tenant.module:signatures'])
    ->name('tenant.signatures.storefront.')
    ->group(function () {
        Route::get('/solicitud', [StorefrontController::class, 'create'])->name('create');
        Route::post('/solicitud', [StorefrontController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('store');
        Route::get('/solicitud/enviada', [StorefrontController::class, 'received'])->name('received');

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

        Route::middleware('permission:tenant.signature-storefront.update')->group(function () {
            Route::get('/sitio-web', [StorefrontSettingsController::class, 'edit'])->name('storefront.edit');
            Route::put('/sitio-web', [StorefrontSettingsController::class, 'update'])->name('storefront.update');
        });
    });
