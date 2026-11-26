<?php

namespace App\Services;

use App\Models\Observation;
use App\Models\StatusHistory;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestStateService
{
    private const TRANSITIONS = [
        SupportRequest::STATUS_REGISTERED => [SupportRequest::STATUS_ASSIGNED, SupportRequest::STATUS_REJECTED],
        SupportRequest::STATUS_ASSIGNED => [SupportRequest::STATUS_IN_PROGRESS, SupportRequest::STATUS_REJECTED],
        SupportRequest::STATUS_IN_PROGRESS => [SupportRequest::STATUS_RESOLVED],
        SupportRequest::STATUS_RESOLVED => [SupportRequest::STATUS_CLOSED],
        SupportRequest::STATUS_CLOSED => [SupportRequest::STATUS_REOPENED],
        SupportRequest::STATUS_REOPENED => [SupportRequest::STATUS_IN_PROGRESS],
        SupportRequest::STATUS_REJECTED => [],
    ];

    private const ROLE_RULES = [
        SupportRequest::STATUS_ASSIGNED => [User::ROLE_ADMIN, User::ROLE_SUPERVISOR],
        SupportRequest::STATUS_IN_PROGRESS => [User::ROLE_OFFICER],
        SupportRequest::STATUS_RESOLVED => [User::ROLE_OFFICER],
        SupportRequest::STATUS_CLOSED => [User::ROLE_SUPERVISOR],
        SupportRequest::STATUS_REOPENED => [User::ROLE_SUPERVISOR],
        SupportRequest::STATUS_REJECTED => [User::ROLE_SUPERVISOR],
    ];

    private const NOTE_REQUIRED = [
        SupportRequest::STATUS_RESOLVED,
        SupportRequest::STATUS_REJECTED,
        SupportRequest::STATUS_REOPENED,
    ];

    public function __construct(private readonly NotificationService $notifications) {}

    public function canTransition(SupportRequest $request, string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$request->status] ?? [], true);
    }

    public function change(SupportRequest $request, string $status, User $actor, ?string $note = null): SupportRequest
    {
        return DB::transaction(function () use ($request, $status, $actor, $note) {
            $from = $request->status;

            if ($from === SupportRequest::STATUS_CLOSED && $status !== SupportRequest::STATUS_REOPENED) {
                $this->fail('Una solicitud CERRADA no puede modificarse mediante operaciones ordinarias.', 'status');
            }

            if (! $this->canTransition($request, $status)) {
                $this->fail("No se permite la transición de {$from} a {$status}.", 'status');
            }

            $this->assertRole($status, $actor);

            if (in_array($status, [SupportRequest::STATUS_IN_PROGRESS, SupportRequest::STATUS_RESOLVED], true)
                && $request->assigned_to !== $actor->id) {
                $this->fail('Solo el funcionario asignado puede atender esta solicitud.', 'status');
            }

            $note = trim((string) $note);

            if (in_array($status, self::NOTE_REQUIRED, true) && $note === '') {
                $this->fail('Esta transición requiere una observación o justificación.', 'note');
            }

            if ($note !== '') {
                Observation::create([
                    'request_id' => $request->id,
                    'user_id' => $actor->id,
                    'body' => $note,
                    'kind' => $this->observationKind($status),
                ]);
            }

            $request->update(['status' => $status]);

            StatusHistory::create([
                'request_id' => $request->id,
                'from_status' => $from,
                'to_status' => $status,
                'changed_by' => $actor->id,
                'note' => $note !== '' ? $note : null,
            ]);

            $this->notifications->notify(
                $request->applicant,
                'status_changed',
                'Cambio de estado de tu solicitud',
                "La solicitud {$request->code} cambió de {$from} a {$status}.",
                $request,
            );

            if ($status === SupportRequest::STATUS_RESOLVED) {
                $this->notifications->notifySupervisors(
                    'Solicitud resuelta pendiente de cierre',
                    "La solicitud {$request->code} fue resuelta y requiere autorización de cierre.",
                    $request,
                );
            }

            return $request->refresh();
        });
    }

    public function addObservation(SupportRequest $request, User $actor, string $body): Observation
    {
        return DB::transaction(function () use ($request, $actor, $body) {
            if ($request->status === SupportRequest::STATUS_CLOSED) {
                $this->fail('Una solicitud CERRADA no puede modificarse.', 'body');
            }

            if ($request->assigned_to !== $actor->id) {
                $this->fail('Solo el funcionario asignado puede registrar observaciones.', 'body');
            }

            $body = trim($body);

            if ($body === '') {
                $this->fail('La observación no puede estar vacía.', 'body');
            }

            return Observation::create([
                'request_id' => $request->id,
                'user_id' => $actor->id,
                'body' => $body,
                'kind' => Observation::KIND_OBSERVATION,
            ]);
        });
    }

    private function assertRole(string $status, User $actor): void
    {
        $allowed = self::ROLE_RULES[$status] ?? null;

        if ($allowed !== null && ! $actor->hasRole(...$allowed)) {
            $this->fail('Su rol no permite realizar esta transición.', 'status');
        }
    }

    private function observationKind(string $status): string
    {
        return match ($status) {
            SupportRequest::STATUS_RESOLVED => Observation::KIND_SOLUTION,
            SupportRequest::STATUS_REJECTED => Observation::KIND_REJECTION,
            SupportRequest::STATUS_REOPENED => Observation::KIND_REOPEN,
            SupportRequest::STATUS_CLOSED => Observation::KIND_CLOSURE,
            default => Observation::KIND_OBSERVATION,
        };
    }

    private function fail(string $message, string $field): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
