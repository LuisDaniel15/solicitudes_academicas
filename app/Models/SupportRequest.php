<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportRequest extends Model
{
    public const STATUS_REGISTERED = 'REGISTRADA';

    public const STATUS_ASSIGNED = 'ASIGNADA';

    public const STATUS_IN_PROGRESS = 'EN_PROCESO';

    public const STATUS_RESOLVED = 'RESUELTA';

    public const STATUS_CLOSED = 'CERRADA';

    public const STATUS_REJECTED = 'RECHAZADA';

    public const STATUS_REOPENED = 'REABIERTA';

    public const STATUSES = [
        self::STATUS_REGISTERED, self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS,
        self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_REJECTED,
        self::STATUS_REOPENED,
    ];

    public const PRIORITIES = ['low', 'medium', 'high'];

    protected $table = 'requests';

    protected $fillable = [
        'code', 'user_id', 'category_id', 'description',
        'priority', 'assigned_to', 'status',
    ];

    protected $attributes = [
        'priority' => 'medium',
        'status' => self::STATUS_REGISTERED,
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(StatusHistory::class, 'request_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class, 'request_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'request_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'request_id');
    }
}
