<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UserPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'preferred_sources' => ['sometimes', 'array'],
            'preferred_sources.*' => ['integer', 'exists:sources,id'],
            'preferred_categories' => ['sometimes', 'array'],
            'preferred_categories.*' => ['integer', 'exists:categories,id'],
            'preferred_authors' => ['sometimes', 'array'],
            'preferred_authors.*' => ['integer', 'exists:authors,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferred_sources.*.exists' => 'One or more selected sources do not exist.',
            'preferred_categories.*.exists' => 'One or more selected categories do not exist.',
            'preferred_authors.*.exists' => 'One or more selected authors do not exist.',
        ];
    }
}
