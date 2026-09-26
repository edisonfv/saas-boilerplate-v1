<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\AppointmentController;
use Modules\Support\Http\Controllers\PublicTicketController;
use Modules\Support\Http\Controllers\TicketController;

/*
|--------------------------------------------------------------------------
| Support Module Routes (Tenant)
|--------------------------------------------------------------------------
|
| Registered with the tenancy middleware by this module's
| RouteServiceProvider. URIs must not collide with central ones (routes are
| not domain-bound): the workspace uses /mi-soporte, the guest form on a
| tenant domain uses /ayuda, and the central public site uses /soporte.
|
*/

// Guest form on the tenant's own domain (e.g. linked from its login page).
// Works even if the tenant hasn't contracted the Support module.
Route::get('/ayuda', [PublicTicketController::class, 'create'])->name('tenant.support.public.create');
Route::post('/ayuda', [PublicTicketController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('tenant.support.public.store');

Route::middleware(['auth', 'tenant.active', 'tenant.module:support'])
    ->prefix('mi-soporte')
    ->name('tenant.support.')
    ->group(function () {
        Route::middleware('permission:tenant.support-tickets.create')->group(function () {
            Route::get('/nuevo', [TicketController::class, 'create'])->name('tickets.create');
            Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        });

        // Permission = may use the feature; policy ("can") = may touch *this* ticket.
        Route::middleware('permission:tenant.support-tickets.view')->group(function () {
            Route::get('/', [TicketController::class, 'index'])->name('tickets.index');
            Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->can('view', 'ticket')->name('tickets.show');
            Route::post('/tickets/{ticket}/mensajes', [TicketController::class, 'reply'])->can('participate', 'ticket')->name('tickets.reply');
            Route::post('/tickets/{ticket}/calificacion', [TicketController::class, 'rate'])->can('participate', 'ticket')->name('tickets.rate');
            Route::get('/adjuntos/{attachment}', [TicketController::class, 'attachment'])->can('view', 'attachment')->name('attachments.show');
        });

        Route::middleware('permission:tenant.support-appointments.create')->group(function () {
            Route::get('/tickets/{ticket}/agendar', [AppointmentController::class, 'create'])->can('participate', 'ticket')->name('appointments.create');
            Route::post('/tickets/{ticket}/citas', [AppointmentController::class, 'store'])->can('participate', 'ticket')->name('appointments.store');
            Route::delete('/tickets/{ticket}/citas/{appointment}', [AppointmentController::class, 'destroy'])
                ->scopeBindings()
                ->can('participate', 'ticket')
                ->name('appointments.destroy');
        });
    });
