<?php

namespace Tests;

use App\Models\LegalDocument;
use App\Support\LegalDocuments;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Создаёт правовые документы для тестов. В production эти документы
     * владелец заводит вручную через админ-панель, сидер их не создаёт.
     */
    protected function seedLegalDocuments(): void
    {
        $documents = [
            [
                'key' => 'privacy_policy',
                'slug' => 'politika-konfidencialnosti',
                'title' => 'Политика в отношении обработки персональных данных',
                'lead' => 'Как студия обрабатывает и защищает персональные данные посетителей и клиентов.',
                'body' => '<p>Обработка персональных данных ведётся в соответствии с Федеральным законом № 152-ФЗ «О персональных данных».</p>',
            ],
            [
                'key' => 'public_offer',
                'slug' => 'offer',
                'title' => 'Пользовательское соглашение',
                'lead' => 'Правила использования сайта арт-студии «Корни».',
                'body' => '<p>Пользовательское соглашение арт-студии «Корни».</p>',
            ],
            [
                'key' => 'personal_data',
                'slug' => 'personal',
                'title' => 'Оплата и возврат',
                'lead' => 'Порядок оплаты, доставки и возврата заказов и услуг студии.',
                'body' => '<p>Условия оплаты, доставки и возврата.</p>',
            ],
        ];

        foreach ($documents as $index => $document) {
            LegalDocument::create($document + [
                'version' => '1.0',
                'is_production_ready' => true,
                'sort' => ($index + 1) * 10,
            ]);
        }

        LegalDocuments::flush();
    }
}
