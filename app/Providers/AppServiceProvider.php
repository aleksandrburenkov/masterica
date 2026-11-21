<?php

namespace App\Providers;

use App\Models\ContentPage;
use App\Models\LegalDocument;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminResetPasswordNotification;
use App\Observers\ContentPageObserver;
use App\Observers\LegalDocumentObserver;
use App\Observers\OrderObserver;
use App\Observers\UserPasswordResetObserver;
use App\Support\LegalDocuments;
use Filament\Actions\Action;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPasswordNotification;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Table;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Filament resolves the notification from the container in its password
        // reset page, so this replaces only the email content, not the flow.
        $this->app->bind(FilamentResetPasswordNotification::class, AdminResetPasswordNotification::class);
    }

    public function boot(): void
    {
        // Единый календарь админки: вместо системного виджета браузера
        // используется календарь Filament, оформленный в стилях проекта.
        DateTimePicker::configureUsing(fn (DateTimePicker $picker) => $picker->native(false));

        // На мобильных экранах таблицы админки показываются карточками:
        // широкие таблицы не уходят в горизонтальную прокрутку, а подписи
        // колонок остаются рядом со значениями.
        Table::configureUsing(fn (Table $table) => $table->stackedOnMobile());

        // Счётчики разделов админки обновляются без перезагрузки страницы:
        // после любого действия Filament (сохранение статуса, модерация отзыва,
        // подтверждение записи, удаление и т.п.) перерисовываем сайдбар, чтобы
        // числовые индикаторы и маркеры групп пересчитали актуальные значения.
        Action::configureUsing(function (Action $action): void {
            $action->after(function ($livewire): void {
                $livewire?->dispatch('refresh-sidebar');
            });
        });

        // Пересчёт остатков при любом изменении статуса заказа (включая правку
        // в админке) и при удалении: списание при выполнении, освобождение
        // резерва при отмене/возврате, возврат остатка при отмене выполненного.
        Order::observe(OrderObserver::class);

        // Реестр страниц сайта (раздел «Страницы сайта») синхронизируется с
        // материалами и правовыми документами при любом их изменении.
        ContentPage::observe(ContentPageObserver::class);
        LegalDocument::observe(LegalDocumentObserver::class);

        // Reset links are one-time credentials: any change to an account's email
        // or password, as well as deletion, revokes all outstanding tokens.
        User::observe(UserPasswordResetObserver::class);

        Paginator::defaultView('vendor.pagination.site');
        Paginator::defaultSimpleView('vendor.pagination.site');

        // Правовые документы доступны во всех шаблонах как $legalDocuments['privacy_policy'] и т.д.
        View::composer('*', function ($view) {
            $view->with('legalDocuments', LegalDocuments::all());
        });

        // Force HTTPS in non-local environments for secure cookies and links.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
