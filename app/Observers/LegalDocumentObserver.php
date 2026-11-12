<?php

namespace App\Observers;

use App\Models\LegalDocument;
use App\Support\SitePages;

/**
 * Синхронизирует правовые документы с реестром страниц: каждый документ
 * доступен по адресу /dokumenty/{слаг}.
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
