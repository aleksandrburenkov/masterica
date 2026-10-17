<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.site');
        Paginator::defaultSimpleView('vendor.pagination.site');

        // Force HTTPS in non-local environments for secure cookies and links.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
