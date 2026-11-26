<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Observation extends Model
{
    public const KIND_OBSERVATION = 'observation';

    public const KIND_SOLUTION = 'solution';

    public const KIND_REJECTION = 'rejection_reason';

    public const KIND_CLOSURE = 'closure_note';

    public const KIND_REOPEN = 'reopen_justification';

    protected $table = 'request_observations';

    protected $fillable = [
        'request_id', 'user_id', 'body', 'kind',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class, 'request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
