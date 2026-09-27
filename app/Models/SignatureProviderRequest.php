<?php

namespace App\Models;

use App\Enums\SignatureRequestStatus;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Central index of a signature sold by a tenant: which tenant request it
 * is, which ledger entry paid for it and the provider's token, so provider
 * webhooks (which only carry the token) can be routed back to the tenant.
 * Holds no personal data — that stays in the tenant's database — but it
 * snapshots the sale's economics for central reporting: `unit_cost` (paid
 * to the provider), `unit_price` (paid by the distributor to central) and
 * `sale_price` (paid by the end customer to the distributor).
 *
 * @property string $id
 * @property string $signature_account_id
 * @property string $tenant_id
 * @property string $tenant_request_id
 * @property string|null $signature_product_id
 * @property string|null $consumption_entry_id
 * @property string|null $unit_cost
 * @property string|null $unit_price
 * @property string|null $sale_price
 * @property string $provider
 * @property string|null $provider_token
 * @property SignatureRequestStatus $status
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $last_event_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read SignatureAccount $account
 * @property-read Tenant $tenant
 * @property-read SignatureProduct|null $product
 * @property-read SignatureLedgerEntry|null $consumption
 */
#[Fillable([
    'signature_account_id', 'tenant_id', 'tenant_request_id', 'signature_product_id', 'consumption_entry_id',
    'unit_cost', 'unit_price', 'sale_price', 'provider', 'provider_token', 'status', 'submitted_at', 'last_event_at',
])]
class SignatureProviderRequest extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<SignatureAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(SignatureAccount::class, 'signature_account_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<SignatureProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(SignatureProduct::class, 'signature_product_id');
    }

    /**
     * @return BelongsTo<SignatureLedgerEntry, $this>
     */
    public function consumption(): BelongsTo
    {
        return $this->belongsTo(SignatureLedgerEntry::class, 'consumption_entry_id');
    }

    /**
     * Sales that still count: their consumption wasn't given back to the
     * distributor (rejected/cancelled request or a manual reversal).
     *
     * @param  Builder<self>  $query
     */
    public function scopeCounted(Builder $query): void
    {
        $query->whereDoesntHave('consumption', fn (Builder $consumption) => $consumption->has('reversal'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'status' => SignatureRequestStatus::class,
            'submitted_at' => 'datetime',
            'last_event_at' => 'datetime',
        ];
    }
}
