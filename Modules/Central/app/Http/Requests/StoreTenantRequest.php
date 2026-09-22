<?php

namespace Modules\Central\Http\Requests;

use App\Enums\BillingPeriod;
use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreTenantRequest extends FormRequest
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
            'id' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('tenants', 'id')],
            'company_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_identifier' => ['nullable', 'string', 'max:50'],
            'country_code' => ['required', 'string', 'size:2', 'alpha:ascii'],
            'timezone' => ['required', 'timezone:all'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_password' => ['required', 'confirmed', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
            'plan_id' => [
                'required',
                'uuid',
                Rule::exists('plans', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'billing_period' => ['required', 'string', Rule::in(BillingPeriod::toValues())],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['plan_id', 'billing_period'])) {
                    return;
                }

                $hasActivePrice = Plan::query()
                    ->whereKey($this->string('plan_id')->toString())
                    ->whereHas('prices', fn ($query) => $query
                        ->where('billing_period', $this->string('billing_period')->toString())
                        ->where('is_active', true))
                    ->exists();

                if (! $hasActivePrice) {
                    $validator->errors()->add('billing_period', 'El plan no tiene un precio activo para este periodo.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => $this->string('country_code')->upper()->toString(),
            'owner_email' => $this->string('owner_email')->lower()->toString(),
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.regex' => 'El identificador solo puede tener minusculas, numeros y guiones (sera el subdominio del tenant).',
        ];
    }
}
