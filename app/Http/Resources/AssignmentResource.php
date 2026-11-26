<?php

namespace App\Http\Resources;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Assignment */
class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'previous_assignee' => $this->whenLoaded('previousAssignee', fn () => $this->previousAssignee?->name),
            'new_assignee' => $this->whenLoaded('newAssignee', fn () => $this->newAssignee->name),
            'assigned_by' => $this->whenLoaded('assignedBy', fn () => $this->assignedBy->name),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
