<?php

namespace App\Providers;

use App\Support\LegalDocuments;
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
