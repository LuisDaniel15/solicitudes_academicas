<?php

namespace App\Http\Requests;

use App\Models\SupportRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'priority' => ['required', Rule::in(SupportRequest::PRIORITIES)],
        ];
    }
}
