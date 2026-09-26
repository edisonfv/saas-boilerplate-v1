<?php

namespace App\Models;

use App\Enums\TicketBillingMode;
use App\Enums\TicketBillingStatus;
use App\Enums\TicketCategory;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\Contracts\Attachable;
use Carbon\CarbonImmutable;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A support request from a tenant (or a prospect via the public form).
 * Always stored centrally — tenant-side screens read and write it through
 * the central connection.
 *
 * @property string $id
 * @property int $number
 * @property string|null $tenant_id
 * @property TicketChannel $channel
 * @property string|null $requester_tenant_user_id
 * @property string $requester_name
 * @property string $requester_email
 * @property string|null $requester_company
 * @property string|null $access_token
 * @property string $subject
 * @property string $description
 * @property TicketCategory $category
 * @property TicketPriority $priority
 * @property TicketStatus $status
 * @property string|null $module_id
 * @property string|null $assigned_to
 * @property string|null $created_by
 * @property bool $covered_by_plan
 * @property TicketBillingMode $billing_mode
 * @property TicketBillingStatus $billing_status
 * @property string|null $fixed_amount
 * @property string|null $billing_reason
 * @property string|null $invoice_reference
 * @property CarbonImmutable|null $first_response_due_at
 * @property CarbonImmutable|null $resolution_due_at
 * @property CarbonImmutable|null $first_responded_at
 * @property CarbonImmutable|null $resolved_at
 * @property string|null $resolved_by
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $last_activity_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'number', 'tenant_id', 'channel', 'requester_tenant_user_id', 'requester_name', 'requester_email',
    'requester_company', 'access_token', 'subject', 'description', 'category', 'priority', 'status',
    'module_id', 'assigned_to', 'created_by', 'covered_by_plan', 'billing_mode', 'billing_status',
    'fixed_amount', 'billing_reason', 'invoice_reference', 'first_response_due_at', 'resolution_due_at',
    'first_responded_at', 'resolved_at', 'resolved_by', 'closed_at', 'last_activity_at',
])]
#[Hidden(['access_token'])]
class SupportTicket extends Model implements Attachable
{
    /** @use HasFactory<SupportTicketFactory> */
    use CentralConnection, HasAttachments, HasFactory, UsesUuidPrimaryKey;

    /**
     * Human-readable code shown to customers and staff, e.g. "SOP-000123".
     */
    public function code(): string
    {
        return 'SOP-'.str_pad((string) $this->number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Guests (public form) follow their ticket with a secret token instead of
     * logging in. The token is stored encrypted so every email can link back.
     */
    public function hasValidAccessToken(?string $token): bool
    {
        return $token !== null && $token !== ''
            && $this->access_token !== null
            && hash_equals($this->access_token, $token);
    }

    public function isOpen(): bool
    {
        return ! $this->status->equals(TicketStatus::Resolved(), TicketStatus::Closed());
    }

    public function isFirstResponseOverdue(): bool
    {
        return $this->first_responded_at === null
            && $this->first_response_due_at !== null
            && $this->first_response_due_at->isPast();
    }

    public function isResolutionOverdue(): bool
    {
        return $this->isOpen()
            && $this->resolution_due_at !== null
            && $this->resolution_due_at->isPast();
    }

    /**
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [TicketStatus::Resolved()->value, TicketStatus::Closed()->value]);
    }

    /**
     * Tickets a tenant user may list: the whole company with
     * tenant.support-tickets.view-all, otherwise only their own. Mirrors
     * SupportTicketPolicy::view() for queries.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->where('tenant_id', tenant()?->getTenantKey())
            ->unless(
                $user->can('tenant.support-tickets.view-all'),
                fn (Builder $own) => $own->where('requester_tenant_user_id', (string) $user->getKey()),
            );
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * @return BelongsTo<CentralUser, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(CentralUser::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<CentralUser, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(CentralUser::class, 'resolved_by');
    }

    /**
     * @return HasMany<SupportTicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    /**
     * @return HasMany<SupportTicketEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(SupportTicketEvent::class);
    }

    /**
     * @return HasMany<SupportTimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(SupportTimeEntry::class);
    }

    /**
     * @return HasMany<SupportAppointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(SupportAppointment::class);
    }

    /**
     * @return HasOne<SupportRating, $this>
     */
    public function rating(): HasOne
    {
        return $this->hasOne(SupportRating::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'access_token' => 'encrypted',
            'channel' => TicketChannel::class,
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'covered_by_plan' => 'boolean',
            'billing_mode' => TicketBillingMode::class,
            'billing_status' => TicketBillingStatus::class,
            'fixed_amount' => 'decimal:2',
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }
}
