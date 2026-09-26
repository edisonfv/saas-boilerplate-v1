<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\CentralUser;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * A file is visible to whoever can view the entity it's attached to, so
 * this policy delegates to that entity's own policy. When a new model
 * starts using HasAttachments, map it to its "guarding" entity in
 * guardedEntity() below.
 */
class AttachmentPolicy
{
    public function view(CentralUser|User $user, Attachment $attachment): Response
    {
        $owner = $attachment->attachable;

        // Internal notes are staff-only, and so are their files.
        if ($owner instanceof SupportTicketMessage && $owner->is_internal && ! $user instanceof CentralUser) {
            return Response::denyAsNotFound();
        }

        $guarded = $this->guardedEntity($owner);

        return $guarded !== null && Gate::forUser($user)->allows('view', $guarded)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * The entity whose "view" permission governs the attachment.
     */
    private function guardedEntity(?Model $owner): ?Model
    {
        return match (true) {
            $owner instanceof SupportTicketMessage => $owner->ticket,
            $owner instanceof SupportTicket => $owner,
            default => null,
        };
    }
}
