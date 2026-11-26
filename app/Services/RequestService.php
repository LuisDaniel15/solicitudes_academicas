<?php

namespace App\Services;

use App\Models\Category;
use App\Models\StatusHistory;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestService
{
    public function create(User $applicant, array $data): SupportRequest
    {
        return DB::transaction(function () use ($applicant, $data) {
            if (! $applicant->active) {
                throw ValidationException::withMessages(['user' => ['Su usuario está desactivado.']]);
            }

            $category = Category::findOrFail($data['category_id']);

            if (! $category->active) {
                throw ValidationException::withMessages(['category_id' => ['La categoría seleccionada no está activa.']]);
            }

            $request = SupportRequest::create([
                'code' => 'TMP-'.Str::random(24),
                'user_id' => $applicant->id,
                'category_id' => $category->id,
                'description' => $data['description'],
                'priority' => $data['priority'],
                'status' => SupportRequest::STATUS_REGISTERED,
            ]);

            $request->update([
                'code' => sprintf('SOL-%d-%06d', now()->year, $request->id),
            ]);

            StatusHistory::create([
                'request_id' => $request->id,
                'from_status' => null,
                'to_status' => SupportRequest::STATUS_REGISTERED,
                'changed_by' => $applicant->id,
                'note' => null,
            ]);

            return $request->refresh();
        });
    }
}
