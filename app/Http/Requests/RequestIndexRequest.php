<?php

namespace App\Http\Requests;

use App\Models\SupportRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(SupportRequest::STATUSES)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'priority' => ['nullable', Rule::in(SupportRequest::PRIORITIES)],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
