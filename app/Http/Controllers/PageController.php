<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use App\Models\Master;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        $masters = Master::query()->active()->with('techniques')->get();

        return view('pages.about', compact('masters'));
    }

    public function howClassesWork(): View
    {
        return view('pages.how-classes-work');
    }

    public function contacts(): View
    {
        return view('pages.contacts');
    }

    public function guides(): View
    {
        $guides = ContentPage::query()->published()->orderBy('sort')->get();

        return view('pages.guides-index', compact('guides'));
    }

    public function guide(ContentPage $page): View
    {
        abort_unless($page->is_published, 404);

        $others = ContentPage::query()
            ->published()
            ->whereKeyNot($page->getKey())
            ->take(2)
            ->get();

        return view('pages.guide', ['page' => $page, 'others' => $others]);
    }

    public function privacy(): View
    {
        return view('pages.legal', [
            'title' => 'Политика конфиденциальности',
            'lead' => 'Демонстрационный текст локальной версии. Финальный документ предоставит владелец.',
            'sections' => [
                ['h' => 'Назначение', 'p' => 'Документ описывает, как в локальной версии платформы обрабатываются данные пользователей. Он не является юридически утверждённой политикой.'],
                ['h' => 'Какие данные собираются', 'p' => 'Имя, контактный телефон и адрес электронной почты взрослого пользователя, необходимые для записи, заказа и доступа к курсу.'],
                ['h' => 'Данные детей', 'p' => 'Аккаунт создаёт взрослый. Отдельный аккаунт ребёнка не создаётся; собирается только минимум, необходимый для занятия (например, возраст ребёнка).'],
                ['h' => 'Хранение', 'p' => 'Данные хранятся в локальной базе проекта и не передаются во внешние сервисы.'],
            ],
        ]);
    }

    public function consent(): View
    {
        return view('pages.legal', [
            'title' => 'Согласие на обработку данных',
            'lead' => 'Демонстрационный текст локальной версии. Финальный документ предоставит владелец.',
            'sections' => [
                ['h' => 'Предмет согласия', 'p' => 'Пользователь подтверждает согласие на обработку контактных данных для оформления записи, заказа и доступа к курсу.'],
                ['h' => 'Маркетинговые сообщения', 'p' => 'Согласие на новости и предложения фиксируется отдельно и является необязательным.'],
                ['h' => 'Отзыв согласия', 'p' => 'Пользователь может изменить согласие, обратившись в студию; соответствующие отметки хранятся в профиле.'],
            ],
        ]);
    }

    public function rules(): View
    {
        return view('pages.legal', [
            'title' => 'Правила записи и заказа',
            'lead' => 'Демонстрационный текст локальной версии. Финальный документ предоставит владелец.',
            'sections' => [
                ['h' => 'Запись на мастер-класс', 'p' => 'Место резервируется на '.setting('rules.hold_minutes', '15').' минут во время оформления. После отправки запись подтверждается, способ оплаты — оплата в студии.'],
                ['h' => 'Отмена', 'p' => setting('rules.cancel_policy')],
                ['h' => 'Заказ работ', 'p' => 'Оплата при получении. Самовывоз — бесплатно, доставка по '.setting('studio.city', 'Брянску').' — '.money((int) setting('pricing.delivery_city_cents', 40000)).'.'],
                ['h' => 'Курсы', 'p' => 'Доступ к курсу открывается после тестовой отметки оплаты. Реальное списание в локальной версии не выполняется.'],
            ],
        ]);
    }
}
