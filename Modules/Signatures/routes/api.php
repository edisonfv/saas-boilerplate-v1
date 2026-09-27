<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Signatures\Http\Controllers\ProviderWebhookController;

/*
|--------------------------------------------------------------------------
| Signatures Module Routes (API, central)
|--------------------------------------------------------------------------
|
| Status webhooks from the certification authority. Register this URL
| (https://<central-domain>/api/signatures/webhooks/uanataca) and the
| UANATACA_WEBHOOK_TOKEN bearer token with Uanataca.
|
*/

Route::post('/signatures/webhooks/uanataca', ProviderWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('signatures.webhooks.uanataca');
