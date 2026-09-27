<?php

namespace Modules\Signatures\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Period of the signatures settlement report. Defaults to the current month.
 */
class SignatureSettlementRequest extends FormRequest
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
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ];
    }

    public function from(): CarbonImmutable
    {
        return $this->filled('desde')
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->string('desde')->toString())->startOfDay()
            : CarbonImmutable::now()->startOfMonth();
    }

    public function to(): CarbonImmutable
    {
        return $this->filled('hasta')
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->string('hasta')->toString())->endOfDay()
            : CarbonImmutable::now()->endOfMonth();
    }
}
