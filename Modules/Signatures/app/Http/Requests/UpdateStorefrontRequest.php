<?php

namespace Modules\Signatures\Http\Requests;

use App\Models\SignatureStorefront;
use App\Rules\RetailPriceFloor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStorefrontRequest extends FormRequest
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
            'headline' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'regex:/^\+?\d{9,15}$/'],
            'whatsapp_message' => ['nullable', 'string', 'max:500'],
            'prices' => ['nullable', 'array'],
            'prices.*' => ['nullable', 'numeric', 'min:0', 'max:99999', new RetailPriceFloor],
            // Editable sections of the public site; an empty list hides it.
            'uses' => ['present', 'array', 'max:8'],
            'uses.*.title' => ['required', 'string', 'max:80'],
            'uses.*.text' => ['required', 'string', 'max:300'],
            'steps' => ['present', 'array', 'max:6'],
            'steps.*.title' => ['required', 'string', 'max:80'],
            'steps.*.text' => ['required', 'string', 'max:300'],
            'faqs' => ['present', 'array', 'max:15'],
            'faqs.*.question' => ['required', 'string', 'max:200'],
            'faqs.*.answer' => ['required', 'string', 'max:1000'],
            // Where customers transfer/deposit; shown on their payment link.
            // Search snippet; empty = generated from the headline/description.
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            // Banner photos kept, in order, with their alt text (uploads go through storeImage).
            'hero_slides' => ['present', 'array', 'max:'.SignatureStorefront::MaxHeroSlides],
            'hero_slides.*.id' => ['required', 'string'],
            'hero_slides.*.alt' => ['required', 'string', 'max:150'],
            'bank_accounts' => ['present', 'array', 'max:6'],
            'bank_accounts.*.bank' => ['required', 'string', 'max:80'],
            'bank_accounts.*.account_type' => ['required', 'string', 'max:40'],
            'bank_accounts.*.number' => ['required', 'string', 'max:30'],
            'bank_accounts.*.holder' => ['required', 'string', 'max:120'],
            'bank_accounts.*.holder_id' => ['required', 'string', 'max:20'],
        ];
    }
}
