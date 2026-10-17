<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'sort', 'is_active'])]
class Technique extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function masterclasses(): HasMany
    {
        return $this->hasMany(Masterclass::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort');
    }
}
