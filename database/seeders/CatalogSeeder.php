<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\ContentPage;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Master;
use App\Models\Masterclass;
use App\Models\Product;
use App\Models\Technique;
use App\Models\WorkshopSession;
use App\Services\StockService;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $techniques = $this->seedTechniques();
        $master = $this->seedMaster();
        $masterclasses = $this->seedMasterclasses($techniques, $master);
        $this->seedSessions($masterclasses, $master);
        $this->seedProducts($techniques);
        $this->seedCourse($master, $techniques['paper']);
        $this->seedContentPages();
    }

    /** @return array<string, Technique> */
    private function seedTechniques(): array
    {
        $rows = [
            ['paper', 'Бумажная лоза', 'Плетение из бумажной лозы — основное направление студии.', 1],
            ['kanzashi', 'Канзаши', 'Броши и украшения из атласных лент.', 2],
            ['macrame', 'Макраме', 'Панно и декоративные изделия в технике макраме.', 3],
            ['foamiran', 'Фоамиран', 'Объёмные цветы и композиции из фоамирана.', 4],
            ['beads', 'Бисер', 'Бисероплетение: украшения и фигурки.', 5],
            ['chenille', 'Синельная проволока', 'Практика формы и сборки из синельной проволоки.', 6],
        ];

        $result = [];
        foreach ($rows as [$key, $name, $description, $sort]) {
            $result[$key] = Technique::query()->updateOrCreate(
                ['slug' => $key.'-'.str()->slug($name)],
                ['name' => $name, 'description' => $description, 'sort' => $sort, 'is_active' => true],
            );
        }

        return $result;
    }

    private function seedMaster(): Master
    {
        return Master::query()->updateOrCreate(
            ['name' => 'Ирина Буренкова'],
            [
                'title' => 'Основатель и мастер арт-студии «Корни»',
                'bio' => 'Ирина Буренкова — основатель арт-студии «Корни». Проводит творческие занятия по направлениям, представленным в каталоге, для детей и взрослых.',
                'specialization' => 'Плетение из бумажной лозы',
                'photo' => 'images/photo-1.jpg',
                'phone' => '+7 (906) 698-31-06',
                'sort' => 1,
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  array<string, Technique>  $techniques
     * @return array<string, Masterclass>
     */
    private function seedMasterclasses(array $techniques, Master $master): array
    {
        $rows = [
            [
                'key' => 'basket',
                'technique' => 'paper',
                'title' => 'Корзина из бумажной лозы',
                'short' => 'Осваиваем приёмы плетения и создаём изделие шаг за шагом.',
                'outcome' => 'Готовая корзина из бумажной лозы, оформленная в выбранном цвете.',
                'description' => 'На занятии вы познакомитесь с материалом и основными приёмами плетения из бумажной лозы. Ведущий покажет, как подготовить трубочки, сплести дно, стенки и ручку. К концу занятия у каждого участника получится аккуратная корзина — её можно забрать домой и использовать дома или подарить.',
                'age_min' => 12,
                'duration' => 120,
                'price' => 150000,
                'image' => 'images/photo-1.jpg',
            ],
            [
                'key' => 'brooch',
                'technique' => 'kanzashi',
                'title' => 'Брошь в технике канзаши',
                'short' => 'Создаём авторское украшение из атласных лент.',
                'outcome' => 'Брошь канзаши с закреплением — готова к носке.',
                'description' => 'Мы разберём, как складывать лепестки из атласных лент, собирать цветок и закреплять брошь на креплении. Мастер объяснит, как выбрать цветовую гамму и аккуратно обработать края.',
                'age_min' => 12,
                'duration' => 90,
                'price' => 120000,
                'image' => 'images/photo-2.jpg',
            ],
            [
                'key' => 'macrame',
                'technique' => 'macrame',
                'title' => 'Панно-макраме',
                'short' => 'Плетём декоративное панно для дома.',
                'outcome' => 'Настенное панно-макраме с деревянной основой.',
                'description' => 'На занятии осваиваем базовые узлы макраме и собираем из них объёмное панно. Вы научитесь рассчитывать длину нитей и завершать изделие декоративной отделкой.',
                'age_min' => 16,
                'duration' => 150,
                'price' => 180000,
                'image' => 'images/photo-3.jpg',
            ],
            [
                'key' => 'foamiran',
                'technique' => 'foamiran',
                'title' => 'Цветы из фоамирана',
                'short' => 'Практика по созданию объёмной цветочной композиции.',
                'outcome' => 'Авторская цветочная композиция из фоамирана.',
                'description' => 'Учимся придавать фоамирану форму, тонировать лепестки и собирать цветок в композицию. Мастер подскажет, как подобрать материалы и закрепить результат.',
                'age_min' => 14,
                'duration' => 120,
                'price' => 160000,
                'image' => 'images/hero-masterclass.png',
            ],
        ];

        $result = [];
        foreach ($rows as $row) {
            $masterclass = Masterclass::query()->updateOrCreate(
                ['slug' => $row['key']],
                [
                    'technique_id' => $techniques[$row['technique']]->id,
                    'title' => $row['title'],
                    'short_description' => $row['short'],
                    'description' => $row['description'],
                    'outcome' => $row['outcome'],
                    'level' => 'beginner',
                    'age_min' => $row['age_min'],
                    'duration_minutes' => $row['duration'],
                    'price_cents' => $row['price'],
                    'currency' => 'RUB',
                    'materials_included' => true,
                    'format' => 'studio',
                    'image' => $row['image'],
                    'sort' => array_search($row['key'], array_column($rows, 'key')) + 1,
                    'is_published' => true,
                    'seo_title' => $row['title'].' — мастер-класс в Брянске',
                    'seo_description' => $row['short'],
                ],
            );
            $result[$row['key']] = $masterclass;
        }

        return $result;
    }

    /**
     * @param  array<string, Masterclass>  $masterclasses
     */
    private function seedSessions(array $masterclasses, Master $master): void
    {
        if (WorkshopSession::query()->exists()) {
            return;
        }

        $timezone = setting('studio.timezone', 'Europe/Moscow');
        $keys = array_keys($masterclasses);

        $slots = [];
        $cursor = Carbon::now($timezone)->startOfDay()->addDay();
        $limit = Carbon::now($timezone)->addDays(30);

        while ($cursor->lessThanOrEqualTo($limit) && count($slots) < 8) {
            $times = match ($cursor->dayOfWeek) {
                Carbon::WEDNESDAY => [18],
                Carbon::SATURDAY, Carbon::SUNDAY => [11, 14],
                default => [],
            };

            foreach ($times as $time) {
                if (count($slots) >= 8) {
                    break;
                }
                $slots[] = $cursor->copy()->setTime($time, 0);
            }

            $cursor->addDay();
        }

        $index = 0;
        foreach ($slots as $startsAtLocal) {
            $masterclass = $masterclasses[$keys[$index % count($keys)]];
            WorkshopSession::create([
                'masterclass_id' => $masterclass->id,
                'master_id' => $master->id,
                'starts_at' => $startsAtLocal->copy()->utc(),
                'timezone' => $timezone,
                'capacity' => Settings::int('pricing.workshop_capacity', 8),
                'price_cents' => null,
                'location_name' => 'Студия «Корни»',
                'address' => setting('studio.address'),
                'status' => 'scheduled',
                'is_published' => true,
            ]);
            $index++;
        }
    }

    /** @param array<string, Technique> $techniques */
    private function seedProducts(array $techniques): void
    {
        $stock = app(StockService::class);

        $rows = [
            [
                'slug' => 'basket-paper-vine',
                'technique' => 'paper',
                'title' => 'Корзина из бумажной лозы',
                'short' => 'Ручная работа, плетение из бумажной лозы.',
                'description' => 'Декоративная корзина, сплетённая вручную из бумажной лозы и покрытая защитным составом. Подойдёт для хранения мелочей или как подарок.',
                'price' => 350000,
                'image' => 'images/photo-1.jpg',
                'variant' => 'Стандарт, 25×20 см',
                'sku' => 'KOR-BASKET-01',
                'stock' => 2,
            ],
            [
                'slug' => 'organizer-paper-vine',
                'technique' => 'paper',
                'title' => 'Органайзер из бумажной лозы',
                'short' => 'Ручная работа, плетение из бумажной лозы.',
                'description' => 'Настольный органайзер для канцелярии и мелочей. Выполнен вручную из бумажной лозы, устойчив и практичен.',
                'price' => 270000,
                'image' => 'images/photo-2.jpg',
                'variant' => 'Натуральный, 18×12 см',
                'sku' => 'KOR-ORG-01',
                'stock' => 3,
            ],
            [
                'slug' => 'macrame-panel',
                'technique' => 'macrame',
                'title' => 'Декоративное панно в технике макраме',
                'short' => 'Настенное панно, ручная работа.',
                'description' => 'Панно в технике макраме на деревянной основе. Станет акцентом интерьера и подчеркнёт естественные текстуры дома.',
                'price' => 420000,
                'image' => 'images/photo-3.jpg',
                'variant' => '40×60 см',
                'sku' => 'KOR-PANNO-01',
                'stock' => 1,
            ],
        ];

        foreach ($rows as $i => $row) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'technique_id' => $techniques[$row['technique']]->id,
                    'title' => $row['title'],
                    'sku' => $row['sku'],
                    'short_description' => $row['short'],
                    'description' => $row['description'],
                    'price_cents' => $row['price'],
                    'currency' => 'RUB',
                    'image' => $row['image'],
                    'sort' => $i + 1,
                    'is_published' => true,
                    'seo_title' => $row['title'].' — купить в Брянске',
                    'seo_description' => $row['short'],
                ],
            );

            $variant = $product->variants()->firstOrNew(['sku' => $row['sku']]);
            $variant->fill([
                'name' => $row['variant'],
                'price_cents' => $row['price'],
                'is_active' => true,
            ]);
            if (! $variant->exists) {
                $variant->stock = 0;
            }
            $variant->save();

            // Record the opening balance only once so re-seeding does not
            // diverge the cached stock from the append-only ledger.
            if ($variant->movements()->count() === 0) {
                $stock->record($variant, StockMovementType::Receipt, $row['stock'], 'Стартовый остаток (seed)');
            }
        }
    }

    private function seedCourse(Master $master, Technique $paper): void
    {
        $course = Course::query()->updateOrCreate(
            ['slug' => 'paper-vine-basket-course'],
            [
                'master_id' => $master->id,
                'technique_id' => $paper->id,
                'title' => 'Корзина из бумажной лозы: от основы до готового изделия',
                'short_description' => 'Полный путь: материалы, подготовка лозы, плетение дна, стенок и ручки.',
                'description' => 'Онлайн-курс для тех, кто хочет освоить плетение из бумажной лозы дома. Шесть модулей и восемнадцать уроков проведут от подготовки материала до готовой корзины.',
                'outcome' => 'Готовая корзина из бумажной лозы и уверенные навыки базовых приёмов плетения.',
                'level' => 'beginner',
                'price_cents' => 490000,
                'currency' => 'RUB',
                'access_days' => 365,
                'image' => 'images/hero-masterclass.png',
                'sort' => 1,
                'is_published' => true,
                'seo_title' => 'Курс «Корзина из бумажной лозы» — онлайн',
                'seo_description' => 'Онлайн-курс по плетению корзины из бумажной лозы: 6 модулей, 18 уроков, доступ 12 месяцев.',
            ],
        );

        if ($course->modules()->count() > 0) {
            return;
        }

        $modules = [
            [
                'title' => 'Модуль 1. Материалы и подготовка',
                'description' => 'Что понадобится и как подготовить рабочее место.',
                'lessons' => [
                    'Вводный урок: обзор курса (бесплатный)',
                    'Материалы и инструменты',
                    'Подготовка бумажной лозы',
                ],
            ],
            [
                'title' => 'Модуль 2. Базовые приёмы',
                'description' => 'Основные элементы плетения.',
                'lessons' => [
                    'Плетение трубочек',
                    'Базовые узлы и элементы',
                    'Плотность и ровность плетения',
                ],
            ],
            [
                'title' => 'Модуль 3. Плетение дна',
                'description' => 'Формирование основания изделия.',
                'lessons' => [
                    'Разметка и каркас',
                    'Плетение круглого дна',
                    'Выравнивание дна',
                ],
            ],
            [
                'title' => 'Модуль 4. Стенки корзины',
                'description' => 'Подъём стенок и объём.',
                'lessons' => [
                    'Подъём стенок',
                    'Плетение стенок с узором',
                    'Формирование формы',
                ],
            ],
            [
                'title' => 'Модуль 5. Ручка и отделка',
                'description' => 'Завершение изделия.',
                'lessons' => [
                    'Плетение ручки',
                    'Закрытие края',
                    'Окрашивание и защита',
                ],
            ],
            [
                'title' => 'Модуль 6. Готовое изделие',
                'description' => 'Сборка, декор и уход.',
                'lessons' => [
                    'Финальная сборка',
                    'Декор и аксессуары',
                    'Уход и хранение изделия',
                ],
            ],
        ];

        $lessonNumber = 0;
        foreach ($modules as $mIndex => $moduleData) {
            $module = CourseModule::create([
                'course_id' => $course->id,
                'title' => $moduleData['title'],
                'description' => $moduleData['description'],
                'sort' => $mIndex + 1,
            ]);

            foreach ($moduleData['lessons'] as $lIndex => $title) {
                $lessonNumber++;
                Lesson::create([
                    'course_module_id' => $module->id,
                    'title' => $title,
                    'slug' => 'lesson-'.$lessonNumber,
                    'content' => 'Урок «'.$title.'»: практические шаги, разбор ошибок и домашнее задание. Видео и материалы доступны внутри личного кабинета.',
                    'video_path' => null,
                    'duration_minutes' => 12 + ($lIndex * 4),
                    'is_preview' => $lessonNumber === 1,
                    'is_published' => true,
                    'sort' => $lIndex + 1,
                ]);
            }
        }
    }

    private function seedContentPages(): void
    {
        $rows = [
            [
                'slug' => 'paper-vine-guide',
                'type' => 'guide',
                'title' => 'Плетение из бумажной лозы: руководство для начинающих',
                'excerpt' => 'С чего начать, какие материалы понадобятся и как сделать первые шаги в плетении.',
                'body' => 'Бумажная лоза — доступный материал для плетения. В этом руководстве мы собрали базовые шаги: подготовка бумаги, скручивание трубочек, первые узлы и небольшие изделия. Начните с простой формы, например подставки, и постепенно переходите к корзинам. Занятия в студии помогут закрепить приёмы под руководством мастера.',
                'image' => 'images/hero-masterclass.png',
            ],
            [
                'slug' => 'how-to-choose-class',
                'type' => 'page',
                'title' => 'Как выбрать мастер-класс',
                'excerpt' => 'Короткие подсказки: формат, возраст, техника и уровень.',
                'body' => 'При выборе занятия обратите внимание на технику, возрастной диапазон, длительность и формат. В карточке сеанса указаны дата, время, ведущий, место и стоимость. Если вы не уверены, свяжитесь со студией — мы подскажем подходящее направление.',
                'image' => 'images/illustration.png',
            ],
        ];

        foreach ($rows as $i => $row) {
            ContentPage::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'type' => $row['type'],
                    'title' => $row['title'],
                    'excerpt' => $row['excerpt'],
                    'body' => $row['body'],
                    'image' => $row['image'],
                    'sort' => $i + 1,
                    'is_published' => true,
                    'seo_title' => $row['title'],
                    'seo_description' => $row['excerpt'],
                    'published_at' => now(),
                ],
            );
        }
    }
}
