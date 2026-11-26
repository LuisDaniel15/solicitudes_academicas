<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STUDENT = 'estudiante';

    public const ROLE_OFFICER = 'funcionario';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLES = [
        self::ROLE_ADMIN, self::ROLE_STUDENT, self::ROLE_OFFICER, self::ROLE_SUPERVISOR,
    ];

    protected $fillable = [
        'name', 'email', 'password', 'role', 'active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function requests()
    {
        return $this->hasMany(SupportRequest::class, 'user_id');
    }

    public function assignedRequests()
    {
        return $this->hasMany(SupportRequest::class, 'assigned_to');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
