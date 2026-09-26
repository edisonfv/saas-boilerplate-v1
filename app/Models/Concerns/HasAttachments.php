<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Gives a central model a polymorphic `attachments` relation. The model
 * must also be registered in the morph map (AppServiceProvider) so the
 * stored `attachable_type` is a stable alias, not a class name.
 */
trait HasAttachments
{
    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
