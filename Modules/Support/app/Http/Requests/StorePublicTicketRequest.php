<?php

namespace Modules\Support\Http\Requests;

use App\Enums\TicketCategory;
use App\Services\Attachments\AttachmentStore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicTicketRequest extends FormRequest
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
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_email' => ['required', 'email', 'max:255'],
            'requester_company' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', 'string', Rule::in(TicketCategory::toValues())],
            // Honeypot: real users never see or fill this field.
            'website' => ['prohibited'],
            ...AttachmentStore::rules(),
        ];
    }
}
