<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SupportRequest;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function summary(): JsonResponse
    {
        $byStatus = SupportRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byCategory = Category::withCount('requests')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'total' => $category->requests_count,
            ]);

        return response()->json([
            'total' => SupportRequest::count(),
            'by_status' => collect(SupportRequest::STATUSES)
                ->mapWithKeys(fn (string $status) => [$status => (int) ($byStatus[$status] ?? 0)]),
            'by_category' => $byCategory,
        ]);
    }
}
