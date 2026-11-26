<?php

namespace App\Http\Requests;

use App\Models\SupportRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(SupportRequest::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
