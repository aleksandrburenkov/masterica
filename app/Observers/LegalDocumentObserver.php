<?php

namespace App\Observers;

use App\Models\LegalDocument;
use App\Support\SitePages;

/**
 * Синхронизирует правовые документы с реестром страниц: они доступны как по
 * адресу /dokumenty/{слаг}, так и по «дружественным» маршрутам
 * (/politika-konfidencialnosti и т.п.).
 */
class LegalDocumentObserver
{
    public function saved(LegalDocument $document): void
    {
        SitePages::syncLegalDocument($document);
    }

    public function deleted(LegalDocument $document): void
    {
        SitePages::removeFor($document);
    }
}
