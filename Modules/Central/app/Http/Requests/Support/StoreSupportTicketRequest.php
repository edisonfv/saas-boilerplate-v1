<?php

namespace Modules\Central\Http\Requests\Support;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Models\Module;
use App\Models\Tenant;
use App\Services\Attachments\AttachmentStore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
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
            'tenant_id' => ['nullable', 'string', Rule::exists(Tenant::class, 'id')],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_email' => ['required', 'email', 'max:255'],
            'requester_company' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', 'string', Rule::in(TicketCategory::toValues())],
            'priority' => ['required', 'string', Rule::in(TicketPriority::toValues())],
            'module_id' => ['nullable', 'uuid', Rule::exists(Module::class, 'id')],
            ...AttachmentStore::rules(),
        ];
    }
}
