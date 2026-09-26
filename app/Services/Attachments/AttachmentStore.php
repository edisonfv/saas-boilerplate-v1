<?php

namespace App\Services\Attachments;

use App\Models\Attachment;
use App\Models\Contracts\Attachable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stores uploads for any model using App\Models\Concerns\HasAttachments on
 * the private "attachments" disk. That disk is deliberately not listed in
 * config('tenancy.filesystem.disks'), so files uploaded from a tenant
 * workspace land in the same central location as the console's.
 */
class AttachmentStore
{
    public const Disk = 'attachments';

    /** Allowed extensions for uploads. */
    public const AllowedMimes = 'pdf,png,jpg,jpeg,webp,gif,txt,csv,doc,docx,xls,xlsx,zip';

    /** Max size per file, in kilobytes. */
    public const MaxKilobytes = 10240;

    public const MaxFiles = 5;

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  string|null  $uploadedBy  central user id when staff uploads
     */
    public function store(Attachable $owner, array $files, ?string $uploadedBy = null): void
    {
        foreach ($files as $file) {
            $owner->attachments()->create([
                'uploaded_by' => $uploadedBy,
                'disk' => self::Disk,
                'path' => $file->store($owner->getMorphClass().'/'.$owner->getKey(), self::Disk),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    /**
     * Validation rules for an `attachments[]` upload field.
     *
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:'.self::MaxFiles],
            'attachments.*' => ['file', 'mimes:'.self::AllowedMimes, 'max:'.self::MaxKilobytes],
        ];
    }
}
