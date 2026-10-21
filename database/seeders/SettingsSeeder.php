<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'studio' => [
                'studio.name' => ['Корни', 'string'],
                'studio.tagline' => ['Арт-студия «Корни»', 'string'],
                'studio.city' => ['Брянске', 'string'],
                'studio.address' => ['г. Брянск, ул. Московская, д. 23', 'string'],
                'studio.phone' => ['+7 (906) 698-31-06', 'string'],
                'studio.hours' => ['Ежедневно 10:00–19:00', 'string'],
                'studio.email' => ['info@korni.local', 'string'],
                'studio.timezone' => ['Europe/Moscow', 'string'],
                'studio.og_description' => ['Творческие занятия и авторские работы в Брянске. Плетение из бумажной лозы, канзаши, макраме, фоамиран.', 'text'],
                'studio.legal_note' => ['Все цены указаны в рублях РФ. Правовые документы размещены в разделе «Правовая информация».', 'text'],
            ],
            'pricing' => [
                'pricing.workshop_price_cents' => ['150000', 'int'],
                'pricing.workshop_duration_minutes' => ['120', 'int'],
                'pricing.workshop_capacity' => ['8', 'int'],
                'pricing.delivery_pickup_cents' => ['0', 'int'],
                'pricing.delivery_city_cents' => ['40000', 'int'],
            ],
            'rules' => [
                'rules.currency' => ['RUB', 'string'],
                'rules.hold_minutes' => ['15', 'int'],
                'rules.free_cancel_hours' => ['24', 'int'],
                'rules.delivery_prep_days' => ['3–5', 'string'],
                'rules.cancel_policy' => ['Бесплатная отмена не позднее чем за 24 часа до начала занятия. При более поздней отмене возврат не предусмотрен.', 'text'],
                'rules.payment_note' => ['Оплата в студии. Платёжный провайдер не подключён — оплата при получении и тестовая отметка оплаты работают локально.', 'text'],
                'rules.materials_note' => ['Материалы включены в стоимость обычного занятия.', 'text'],
            ],
        ];

        foreach ($groups as $group => $items) {
            foreach ($items as $key => [$value, $type]) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['group' => $group, 'value' => $value, 'type' => $type],
                );
            }
        }

        Settings::flush();
    }
}
