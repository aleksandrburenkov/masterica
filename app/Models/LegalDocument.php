<?php

namespace App\Models;

use App\Support\LegalDocuments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Правовой документ платформы (политика, согласие, оферта, лицензия).
 * Тексты редактируются владельцем в админ-панели (Filament), см. LegalDocumentResource.
 */
#[Fillable([
    'key', 'slug', 'title', 'lead', 'body', 'version',
    'effective_date', 'is_production_ready', 'sort',
])]
class LegalDocument extends Model
{
    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'is_production_ready' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => LegalDocuments::flush());
        static::deleted(fn () => LegalDocuments::flush());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeProductionReady($query)
    {
        return $query->where('is_production_ready', true);
    }
}
