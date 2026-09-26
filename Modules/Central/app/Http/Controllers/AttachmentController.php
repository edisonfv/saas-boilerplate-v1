<?php

namespace Modules\Central\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Services\Attachments\AttachmentStore;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads a polymorphic attachment of any central entity. Authorization
 * happens on the route ("can:view,attachment" → AttachmentPolicy).
 */
class AttachmentController extends Controller
{
    public function show(Attachment $attachment, AttachmentStore $store): StreamedResponse
    {
        return $store->download($attachment);
    }
}
