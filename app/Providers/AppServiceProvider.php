<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use App\Support\LegalDocuments;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Единый календарь админки: вместо системного виджета браузера
        // используется календарь Filament, оформленный в стилях проекта.
        DateTimePicker::configureUsing(fn (DateTimePicker $picker) => $picker->native(false));

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
