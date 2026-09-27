<?php

namespace App\Models;

use App\Enums\SignatureDocumentKind;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A KYC document of a signature request (identity card photos, selfie,
 * RUC, company appointment...), one file per typed slot.
 *
 * Deliberately NOT the central polymorphic App\Models\Attachment: these
 * are the tenant's customers' identity documents, so they must stay in
 * the tenant's own database and on its tenant-suffixed "local" disk (data
 * isolation per LOPDP), and each file fills a provider-mandated slot
 * (SignatureDocumentKind) rather than being a free-form attachment.
 *
 * @property string $id
 * @property string $signature_request_id
 * @property SignatureDocumentKind $kind
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int $size
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['signature_request_id', 'kind', 'disk', 'path', 'original_name', 'mime_type', 'size'])]
class SignatureRequestDocument extends Model
{
    use UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<SignatureRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(SignatureRequest::class, 'signature_request_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => SignatureDocumentKind::class,
            'size' => 'integer',
        ];
    }
}
