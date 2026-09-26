<?php

namespace App\Models\Contracts;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A central model that can own files. Implement it together with the
 * App\Models\Concerns\HasAttachments trait, and register the model's alias
 * in the morph map (AppServiceProvider).
 */
interface Attachable
{
    /**
     * @return MorphMany<Attachment, covariant \Illuminate\Database\Eloquent\Model>
     */
    public function attachments(): MorphMany;

    /**
     * Implemented by Eloquent\Model (declared without a native return type
     * there, so it can't be added here either).
     *
     * @return string
     */
    public function getMorphClass();

    /**
     * @return mixed
     */
    public function getKey();
}
