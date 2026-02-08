<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ArticleIndexRequest extends FormRequest
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
            'keyword' => ['sometimes', 'string', 'max:255'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'source_ids' => ['sometimes', 'array'],
            'source_ids.*' => ['integer', 'exists:sources,id'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'author_ids' => ['sometimes', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
            'sort_by' => ['sometimes', 'string', 'in:published_at,created_at,title'],
            'sort_direction' => ['sometimes', 'string', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'source_ids.*.exists' => 'One or more selected sources do not exist.',
            'category_ids.*.exists' => 'One or more selected categories do not exist.',
            'author_ids.*.exists' => 'One or more selected authors do not exist.',
        ];
    }
}
