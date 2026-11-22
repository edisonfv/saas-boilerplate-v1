<?php

namespace Modules\Central\Http\Requests\Signatures;

use App\Enums\SalesPeriod;
use App\Models\SignatureProduct;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filters of the central signature sales report (query string).
 */
class SignatureSalesReportRequest extends FormRequest
{
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
        return [
            'period' => ['nullable', 'string', Rule::in(SalesPeriod::toValues())],
            'from' => ['nullable', 'date', 'required_if:period,Custom'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'required_if:period,Custom'],
            'tenant' => ['nullable', 'string', Rule::exists(Tenant::class, 'id')],
            'product' => ['nullable', 'string', Rule::exists(SignatureProduct::class, 'id')],
        ];
    }

    public function period(): SalesPeriod
    {
        return SalesPeriod::from($this->validated('period') ?? SalesPeriod::ThisMonth()->value);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(): array
    {
        return $this->period()->range(
            $this->validated('from') ? CarbonImmutable::parse($this->validated('from')) : null,
            $this->validated('to') ? CarbonImmutable::parse($this->validated('to')) : null,
        );
    }

    public function tenantId(): ?string
    {
        return $this->validated('tenant');
    }

    public function productId(): ?string
    {
        return $this->validated('product');
    }
}
