<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\PublicTicketController;

/*
|--------------------------------------------------------------------------
| Support Module Routes (Central, public)
|--------------------------------------------------------------------------
|
| Guest-facing pages on the central site: leave a ticket, follow it with the
| secret token from the confirmation email, and rate it via a signed link.
| Signed links are generated relative (see App\Services\Support\SupportLinks)
| so they validate regardless of host.
|
*/

Route::prefix('soporte')->name('support.public.')->group(function () {
    Route::get('/nuevo', [PublicTicketController::class, 'create'])->name('create');
    Route::post('/nuevo', [PublicTicketController::class, 'store'])->middleware('throttle:5,1')->name('store');

    Route::get('/seguimiento/{ticket}', [PublicTicketController::class, 'show'])->name('tickets.show');
    Route::post('/seguimiento/{ticket}/mensajes', [PublicTicketController::class, 'reply'])
        ->middleware('throttle:20,1')
        ->name('tickets.reply');
    Route::get('/seguimiento/{ticket}/adjuntos/{attachment}', [PublicTicketController::class, 'attachment'])->name('attachments.show');

    Route::middleware('signed:relative')->group(function () {
        Route::get('/calificar/{ticket}', [PublicTicketController::class, 'rating'])->name('rating.show');
        Route::post('/calificar/{ticket}', [PublicTicketController::class, 'rate'])->name('rating.store');
    });
});
