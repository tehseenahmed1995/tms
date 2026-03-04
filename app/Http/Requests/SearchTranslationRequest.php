<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchTranslationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize tags parameter: convert comma-separated string to array
        if ($this->has('tags') && is_string($this->input('tags'))) {
            $this->merge([
                'tags' => array_filter(array_map('trim', explode(',', $this->input('tags'))))
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'key' => 'sometimes|string|max:255',
            'locale' => 'sometimes|string|size:2',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'content' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
