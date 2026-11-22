<?php

namespace Modules\Signatures\Http\Requests;

use App\Enums\Gender;
use App\Enums\IdentityDocumentType;
use App\Enums\SignatureApplicantType;
use App\Enums\SignatureDocumentKind;
use App\Models\SignatureProduct;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Shared validation of a signature application (applicant data + KYC
 * documents), following the provider's field constraints. Subclasses
 * decide whether documents are mandatory (new request) or optional
 * (editing a draft that already has them).
 */
abstract class SignatureApplicationRequest extends FormRequest
{
    /**
     * Whether every required document slot must come with this request.
     */
    abstract protected function documentsAreRequired(): bool;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isCedula = $this->input('document_type') === IdentityDocumentType::Cedula()->value;
        $requiresCompany = $this->applicantType()?->requiresCompany() ?? false;
        $requiresLegalRepresentative = $this->applicantType()?->requiresLegalRepresentative() ?? false;

        return [
            'signature_product_id' => ['required', 'string', Rule::exists(SignatureProduct::class, 'id')->where('is_active', true)],
            'applicant_type' => ['required', 'string', Rule::in(SignatureApplicantType::toValues())],
            'first_names' => ['required', 'string', 'max:120'],
            'first_surname' => ['required', 'string', 'max:60'],
            'second_surname' => ['nullable', 'string', 'max:60'],
            'document_type' => ['required', 'string', Rule::in(IdentityDocumentType::toValues())],
            'document_number' => $isCedula
                ? ['required', 'digits:10', $this->cedulaChecksum()]
                : ['required', 'string', 'alpha_num', 'max:20'],
            'fingerprint_code' => [Rule::requiredIf($isCedula), 'nullable', 'string', 'alpha_num', 'between:6,10'],
            // A natural person's RUC is their cédula followed by "001".
            'personal_ruc' => [
                Rule::requiredIf($this->applicantType()?->requiresPersonalRuc() ?? false),
                'nullable',
                'digits:13',
                ...($isCedula && $this->filled('document_number')
                    ? [Rule::in([$this->input('document_number').'001'])]
                    : []),
            ],
            'gender' => ['required', 'string', Rule::in(Gender::toValues())],
            'birth_date' => ['required', 'date', 'before:today'],
            'nationality' => ['required', 'string', 'max:60'],
            'mobile_phone' => ['required', 'regex:/^09\d{8}$/'],
            'landline_phone' => ['nullable', 'regex:/^0\d{8}$/'],
            'email' => ['required', 'email', 'max:255'],
            'province' => ['required', 'string', 'max:60'],
            'city' => ['required', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:100'],
            'company_name' => [Rule::requiredIf($requiresCompany), 'nullable', 'string', 'max:255'],
            'company_ruc' => [Rule::requiredIf($requiresCompany), 'nullable', 'digits:13'],
            'position' => [Rule::requiredIf($requiresCompany), 'nullable', 'string', 'max:120'],
            'legal_representative_first_names' => [Rule::requiredIf($requiresLegalRepresentative), 'nullable', 'string', 'max:120'],
            'legal_representative_surnames' => [Rule::requiredIf($requiresLegalRepresentative), 'nullable', 'string', 'max:120'],
            'legal_representative_document_type' => [Rule::requiredIf($requiresLegalRepresentative), 'nullable', 'string', Rule::in(IdentityDocumentType::toValues())],
            'legal_representative_document_number' => [Rule::requiredIf($requiresLegalRepresentative), 'nullable', 'string', 'alpha_num', 'max:20'],
            'documents' => ['nullable', 'array'],
            ...$this->documentRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'personal_ruc.in' => 'El RUC personal debe ser tu número de cédula seguido de 001.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(SignatureDocumentKind::toArray())
            ->mapWithKeys(fn (string $label, int|string $kind) => ["documents.{$kind}" => $label])
            ->all() + [
                'signature_product_id' => 'firma',
                'first_names' => 'nombres',
                'first_surname' => 'primer apellido',
                'document_number' => 'número de documento',
                'fingerprint_code' => 'código dactilar',
                'mobile_phone' => 'celular',
                'landline_phone' => 'teléfono fijo',
                'birth_date' => 'fecha de nacimiento',
                'company_ruc' => 'RUC de la empresa',
                'personal_ruc' => 'RUC personal',
            ];
    }

    /**
     * Uploaded documents keyed by SignatureDocumentKind value.
     *
     * @return array<string, UploadedFile>
     */
    public function documents(): array
    {
        return array_filter(
            (array) $this->file('documents', []),
            fn (mixed $file, string $kind) => $file instanceof UploadedFile && in_array($kind, SignatureDocumentKind::toValues(), true),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function product(): SignatureProduct
    {
        return SignatureProduct::query()->findOrFail($this->string('signature_product_id')->toString());
    }

    protected function applicantType(): ?SignatureApplicantType
    {
        return SignatureApplicantType::tryFrom((string) $this->input('applicant_type'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function documentRules(): array
    {
        $required = collect($this->applicantType() !== null ? SignatureDocumentKind::requiredFor($this->applicantType()) : [])
            ->map(fn (SignatureDocumentKind $kind) => $kind->value);
        $maxKilobytes = (int) config('signatures.max_document_kilobytes', 13312);

        return collect(SignatureDocumentKind::cases())
            ->mapWithKeys(fn (SignatureDocumentKind $kind) => ["documents.{$kind->value}" => [
                $this->documentsAreRequired() && $required->contains($kind->value) ? 'required' : 'nullable',
                'file',
                "mimes:{$kind->mimes()}",
                "max:{$maxKilobytes}",
            ]])
            ->all();
    }

    /**
     * Ecuadorian cédula check digit (modulo 10).
     */
    private function cedulaChecksum(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $digits = array_map('intval', str_split((string) $value));
            $province = $digits[0] * 10 + $digits[1];

            if (count($digits) !== 10 || ! (($province >= 1 && $province <= 24) || $province === 30) || $digits[2] > 5) {
                $fail('El número de cédula no es válido.');

                return;
            }

            $sum = 0;

            foreach (array_slice($digits, 0, 9) as $index => $digit) {
                $product = $digit * ($index % 2 === 0 ? 2 : 1);
                $sum += $product > 9 ? $product - 9 : $product;
            }

            if ((10 - $sum % 10) % 10 !== $digits[9]) {
                $fail('El número de cédula no es válido.');
            }
        };
    }
}
