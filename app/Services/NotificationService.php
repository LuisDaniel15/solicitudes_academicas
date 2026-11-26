<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SupportRequest;
use App\Models\User;

class NotificationService
{
    public function notify(
        User $user,
        string $type,
        string $title,
        string $body,
        ?SupportRequest $request = null,
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'request_id' => $request?->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
        ]);
    }

    public function notifySupervisors(
        string $title,
        string $body,
        SupportRequest $request,
    ): void {
        User::where('role', User::ROLE_SUPERVISOR)
            ->where('active', true)
            ->each(fn (User $supervisor) => $this->notify($supervisor, 'request_resolved', $title, $body, $request));
    }
}
