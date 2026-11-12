<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Route;

/**
 * Публичная страница сайта в едином реестре. Используется разделом админки
 * «Настройки» → «Страницы сайта»: перечень всех страниц с переключателем
 * активности и общим текстом «на техническом обслуживании».
 */
#[Fillable([
    'source_type', 'source_id', 'key', 'title', 'group',
    'route_name', 'route_params', 'is_active', 'sort',
])]
class SitePage extends Model
{
    protected function casts(): array
    {
        return [
            'route_params' => 'array',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /** Публичный адрес страницы. Пустая строка, если маршрут недоступен. */
    public function url(): string
    {
        if (! Route::has($this->route_name)) {
            return '';
        }

        return route($this->route_name, $this->route_params ?? []);
    }

    /** Адрес для отображения: путь без схемы и домена. */
    public function path(): string
    {
        $url = $this->url();

        if ($url === '') {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
