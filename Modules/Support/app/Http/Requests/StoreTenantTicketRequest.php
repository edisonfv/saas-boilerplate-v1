<?php

namespace Modules\Support\Http\Requests;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Services\Attachments\AttachmentStore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantTicketRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', 'string', Rule::in(TicketCategory::toValues())],
            // Customers can't self-declare "Urgent"; staff escalates if needed.
            'priority' => ['required', 'string', Rule::in([
                TicketPriority::Low()->value, TicketPriority::Normal()->value, TicketPriority::High()->value,
            ])],
            ...AttachmentStore::rules(),
        ];
    }
}
