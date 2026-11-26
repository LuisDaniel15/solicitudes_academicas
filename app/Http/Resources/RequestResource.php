<?php

namespace App\Http\Resources;

use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SupportRequest */
class RequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'applicant' => $this->whenLoaded('applicant', fn () => [
                'id' => $this->applicant->id,
                'name' => $this->applicant->name,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee?->name),
            'assigned_to' => $this->assigned_to,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'status_history' => $this->whenLoaded('statusHistory', fn () => StatusHistoryResource::collection($this->statusHistory)),
            'observations' => $this->whenLoaded('observations', fn () => ObservationResource::collection($this->observations)),
            'assignments' => $this->whenLoaded('assignments', fn () => AssignmentResource::collection($this->assignments)),
        ];
    }
}
