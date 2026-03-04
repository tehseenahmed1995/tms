<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportTranslationRequest extends FormRequest
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
        $modifications = [];

        // Normalize tags parameter: convert comma-separated string to array
        if ($this->has('tags') && is_string($this->input('tags'))) {
            $modifications['tags'] = array_filter(array_map('trim', explode(',', $this->input('tags'))));
        }

        // Normalize nested parameter: convert string "true"/"false" to boolean
        // Only accept valid boolean strings: "true", "false", "1", "0"
        if ($this->has('nested') && is_string($this->input('nested'))) {
            $nestedValue = strtolower($this->input('nested'));
            if (in_array($nestedValue, ['true', '1', 'false', '0'], true)) {
                $modifications['nested'] = filter_var($nestedValue, FILTER_VALIDATE_BOOLEAN);
            }
            // If invalid, leave as-is so validation will catch it
        }

        if (!empty($modifications)) {
            $this->merge($modifications);
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
            'locale' => 'required|string|size:2',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
            'nested' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'locale.required' => 'The locale field is required.',
            'locale.size' => 'The locale must be exactly 2 characters.',
            'tags.array' => 'The tags field must be an array.',
            'tags.*.string' => 'Each tag must be a string.',
            'nested.boolean' => 'The nested field must be true or false.',
        ];
    }
}
