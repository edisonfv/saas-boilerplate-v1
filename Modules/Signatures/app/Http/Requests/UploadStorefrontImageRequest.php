<?php

namespace Modules\Signatures\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A photo for the public website's banner, with its alternative text
 * (read by screen readers and search engines).
 */
class UploadStorefrontImageRequest extends FormRequest
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
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=800,min_height=400'],
            'alt' => ['required', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.dimensions' => 'Usa una foto de al menos 800 × 400 px para que el banner se vea nítido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['image' => 'foto', 'alt' => 'descripción de la foto'];
    }
}
