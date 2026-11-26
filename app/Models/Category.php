<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name', 'description', 'active',
    ];

    protected $attributes = [
        'active' => true,
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
    }
}
