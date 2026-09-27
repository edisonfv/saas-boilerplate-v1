<?php

namespace App\Models;

use App\Enums\SignatureAffiliationMode;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Services\Signatures\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A tenant's affiliation as a signature distributor. Mutated only through
 * App\Services\Signatures\SignatureWallet, which keeps `credit_used` and
 * the per-product balances in step with the ledger.
 *
 * @property string $id
 * @property string $tenant_id
 * @property SignatureAffiliationMode $affiliation_mode
 * @property string $credit_limit
 * @property string $credit_used
 * @property bool $is_active
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Tenant $tenant
 */
#[Fillable(['tenant_id', 'affiliation_mode', 'credit_limit', 'is_active', 'notes'])]
class SignatureAccount extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    protected $attributes = [
        'credit_limit' => 0,
        'credit_used' => 0,
        'is_active' => true,
    ];

    public function isCredit(): bool
    {
        return $this->affiliation_mode->equals(SignatureAffiliationMode::Credit());
    }

    /**
     * Credit line still available, in cents (never negative).
     */
    public function availableCreditCents(): int
    {
        return max(0, Money::toCents($this->credit_limit) - Money::toCents($this->credit_used));
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasMany<SignatureBalance, $this>
     */
    public function balances(): HasMany
    {
        return $this->hasMany(SignatureBalance::class);
    }

    /**
     * @return HasMany<SignatureLedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(SignatureLedgerEntry::class);
    }

    /**
     * @return HasMany<SignatureProviderRequest, $this>
     */
    public function providerRequests(): HasMany
    {
        return $this->hasMany(SignatureProviderRequest::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'affiliation_mode' => SignatureAffiliationMode::class,
            'credit_limit' => 'decimal:2',
            'credit_used' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
