<?php

namespace App\Services\Support;

use App\Enums\TicketAuthorType;
use App\Models\CentralUser;

/**
 * Who performs an action on a ticket — recorded on messages and the audit
 * trail. Requesters aren't central users (they live in tenant databases or
 * are anonymous), so they're identified by name only.
 */
final readonly class SupportActor
{
    public function __construct(
        public TicketAuthorType $type,
        public string $name,
        public ?string $centralUserId = null,
    ) {}

    public static function staff(CentralUser $user): self
    {
        return new self(TicketAuthorType::Staff(), $user->name, $user->id);
    }

    public static function requester(string $name): self
    {
        return new self(TicketAuthorType::Requester(), $name);
    }

    public static function system(): self
    {
        return new self(TicketAuthorType::System(), 'Sistema');
    }

    public function isStaff(): bool
    {
        return $this->type->equals(TicketAuthorType::Staff());
    }
}
