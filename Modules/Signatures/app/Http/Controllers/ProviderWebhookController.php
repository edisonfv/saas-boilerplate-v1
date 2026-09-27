<?php

namespace Modules\Signatures\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Signatures\Contracts\SignatureProvider;
use App\Services\Signatures\SignatureStatusSynchronizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Status callbacks from the certification authority (central, stateless).
 * The provider authenticates with the Bearer token we registered with it.
 */
class ProviderWebhookController extends Controller
{
    public function __invoke(Request $request, SignatureProvider $provider, SignatureStatusSynchronizer $synchronizer): JsonResponse
    {
        abort_unless($provider->verifyWebhook($request), 401);

        $payload = $request->json()->all();

        abort_if($payload === [], 422, 'Empty payload.');

        $event = $synchronizer->receive($payload);

        return response()->json([
            'result' => true,
            'processed' => $event->processed_at !== null,
        ]);
    }
}
