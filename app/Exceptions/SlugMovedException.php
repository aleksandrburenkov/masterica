<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Бросается при обращении к устаревшему слагу публичной страницы.
 * Обработчик исключений превращает её в 301-редирект на актуальный адрес.
 */
class SlugMovedException extends RuntimeException
{
    public function __construct(public readonly string $redirectUrl)
    {
        parent::__construct('Slug moved to '.$redirectUrl);
    }
}
