<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A private file attached to any central entity (support tickets, their
 * messages, and whatever comes next) through a polymorphic relation — add
 * App\Models\Concerns\HasAttachments to a model and register its alias in
 * the morph map (AppServiceProvider). Files live on the private
 * "attachments" disk; downloads go through AttachmentPolicy.
 *
 * @property string $id
 * @property string $attachable_type
 * @property string $attachable_id
 * @property string|null $uploaded_by
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int $size
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Model $attachable
 */
#[Fillable(['uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size'])]
class Attachment extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<CentralUser, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(CentralUser::class, 'uploaded_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }
}
