<?php

namespace App\Models;

use App\Models\Concerns\HasMediaFiles;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'title', 'bio', 'specialization', 'photo', 'email', 'phone', 'sort', 'is_active'])]
class Master extends Model
{
    use HasMediaFiles;

    public function mediaAttributes(): array
    {
        return ['photo'];
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function techniques(): BelongsToMany
    {
        return $this->belongsToMany(Technique::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(WorkshopSession::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort');
    }
}
