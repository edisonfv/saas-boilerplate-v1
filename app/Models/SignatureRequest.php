<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\IdentityDocumentType;
use App\Enums\SignatureApplicantType;
use App\Enums\SignatureContainer;
use App\Enums\SignatureDocumentKind;
use App\Enums\SignaturePaymentStatus;
use App\Enums\SignatureRequestSource;
use App\Enums\SignatureRequestStatus;
use App\Enums\SignatureValidity;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A signature a tenant sells to one of its customers. Lives in the
 * tenant's own database (it carries the applicant's personal data); the
 * product is a snapshot of the central catalog taken at capture time.
 *
 * @property string $id
 * @property int $number
 * @property SignatureRequestSource $source
 * @property SignatureRequestStatus $status
 * @property SignaturePaymentStatus $payment_status
 * @property SignatureApplicantType $applicant_type
 * @property string $signature_product_id
 * @property string $product_name
 * @property SignatureValidity $validity
 * @property SignatureContainer $container
 * @property string|null $sale_price
 * @property string $first_names
 * @property string $first_surname
 * @property string|null $second_surname
 * @property IdentityDocumentType $document_type
 * @property string $document_number
 * @property string|null $fingerprint_code
 * @property string|null $personal_ruc
 * @property Gender $gender
 * @property CarbonImmutable $birth_date
 * @property string $nationality
 * @property string $mobile_phone
 * @property string|null $landline_phone
 * @property string $email
 * @property string $province
 * @property string $city
 * @property string $address
 * @property string|null $company_name
 * @property string|null $company_ruc
 * @property string|null $position
 * @property string|null $legal_representative_first_names
 * @property string|null $legal_representative_surnames
 * @property IdentityDocumentType|null $legal_representative_document_type
 * @property string|null $legal_representative_document_number
 * @property string|null $provider_token
 * @property string|null $central_reference
 * @property string|null $last_error
 * @property string|null $certificate_serial
 * @property CarbonImmutable|null $certificate_valid_from
 * @property CarbonImmutable|null $certificate_valid_to
 * @property string|null $created_by
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $issued_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'number', 'source', 'status', 'payment_status', 'applicant_type', 'signature_product_id', 'product_name', 'validity', 'container',
    'sale_price', 'first_names', 'first_surname', 'second_surname', 'document_type', 'document_number',
    'fingerprint_code', 'personal_ruc', 'gender', 'birth_date', 'nationality', 'mobile_phone', 'landline_phone',
    'email', 'province', 'city', 'address', 'company_name', 'company_ruc', 'position',
    'legal_representative_first_names', 'legal_representative_surnames', 'legal_representative_document_type',
    'legal_representative_document_number', 'provider_token', 'central_reference', 'last_error',
    'certificate_serial', 'certificate_valid_from', 'certificate_valid_to', 'created_by', 'submitted_at', 'issued_at',
])]
class SignatureRequest extends Model
{
    use UsesUuidPrimaryKey;

    public function code(): string
    {
        return 'FE-'.str_pad((string) $this->number, 6, '0', STR_PAD_LEFT);
    }

    public function applicantName(): string
    {
        return trim("{$this->first_names} {$this->first_surname} {$this->second_surname}");
    }

    /**
     * Required document slots for this applicant type that have no file yet.
     *
     * @return list<SignatureDocumentKind>
     */
    public function missingDocuments(): array
    {
        $uploaded = $this->documents->map(fn (SignatureRequestDocument $document) => $document->kind->value)->all();

        return array_values(array_filter(
            SignatureDocumentKind::requiredFor($this->applicant_type),
            fn (SignatureDocumentKind $kind) => ! in_array($kind->value, $uploaded, true),
        ));
    }

    /**
     * @return HasMany<SignatureRequestDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(SignatureRequestDocument::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status->equals(SignaturePaymentStatus::Paid());
    }

    /**
     * @return HasMany<SignaturePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SignaturePayment::class);
    }

    /**
     * @return HasMany<SignatureRequestEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(SignatureRequestEvent::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || $term === '') {
            return;
        }

        $query->where(fn (Builder $search) => $search
            ->where('document_number', 'like', "%{$term}%")
            ->orWhere('first_names', 'like', "%{$term}%")
            ->orWhere('first_surname', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('company_name', 'like', "%{$term}%"));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'source' => SignatureRequestSource::class,
            'status' => SignatureRequestStatus::class,
            'payment_status' => SignaturePaymentStatus::class,
            'applicant_type' => SignatureApplicantType::class,
            'validity' => SignatureValidity::class,
            'container' => SignatureContainer::class,
            'sale_price' => 'decimal:2',
            'document_type' => IdentityDocumentType::class,
            'gender' => Gender::class,
            'birth_date' => 'date',
            'legal_representative_document_type' => IdentityDocumentType::class.':nullable',
            'certificate_valid_from' => 'datetime',
            'certificate_valid_to' => 'datetime',
            'submitted_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }
}
