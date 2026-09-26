<?php

namespace Modules\Support\Http\Requests;

use App\Services\Attachments\AttachmentStore;
use Illuminate\Foundation\Http\FormRequest;

class ReplyAsRequesterRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:10000'],
            ...AttachmentStore::rules(),
        ];
    }
}
