<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(
        private readonly RequestStateService $state,
        private readonly NotificationService $notifications,
    ) {}

    public function assign(SupportRequest $request, User $officer, User $actor): SupportRequest
    {
        return DB::transaction(function () use ($request, $officer, $actor) {
            if (! $actor->hasRole(User::ROLE_ADMIN, User::ROLE_SUPERVISOR)) {
                $this->fail('Su rol no permite gestionar asignaciones.', 'assigned_to');
            }

            if (! $officer->active || ! $officer->hasRole(User::ROLE_OFFICER)) {
                $this->fail('El responsable debe ser un funcionario activo.', 'assigned_to');
            }

            if (in_array($request->status, [
                SupportRequest::STATUS_CLOSED,
                SupportRequest::STATUS_RESOLVED,
                SupportRequest::STATUS_REJECTED,
            ], true)) {
                $this->fail('Esta solicitud ya no puede asignarse.', 'assigned_to');
            }

            Assignment::create([
                'request_id' => $request->id,
                'previous_assignee_id' => $request->assigned_to,
                'new_assignee_id' => $officer->id,
                'assigned_by' => $actor->id,
            ]);

            $request->update(['assigned_to' => $officer->id]);

            if ($request->status === SupportRequest::STATUS_REGISTERED) {
                $this->state->change($request, SupportRequest::STATUS_ASSIGNED, $actor);
            }

            $this->notifications->notify(
                $officer,
                'request_assigned',
                'Solicitud asignada',
                "Se le asignó la solicitud {$request->code}.",
                $request,
            );

            return $request->refresh();
        });
    }

    private function fail(string $message, string $field): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
