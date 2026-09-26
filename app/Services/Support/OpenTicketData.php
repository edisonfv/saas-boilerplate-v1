<?php

namespace App\Services\Support;

use App\Enums\TicketCategory;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Models\CentralUser;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;

/**
 * Everything needed to open a ticket, from any channel.
 */
final readonly class OpenTicketData
{
    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function __construct(
        public TicketChannel $channel,
        public string $requesterName,
        public string $requesterEmail,
        public string $subject,
        public string $description,
        public TicketCategory $category,
        public TicketPriority $priority,
        public ?Tenant $tenant = null,
        public ?string $requesterTenantUserId = null,
        public ?string $requesterCompany = null,
        public ?string $moduleId = null,
        public ?CentralUser $createdBy = null,
        public array $attachments = [],
    ) {}
}
