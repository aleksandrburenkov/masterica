<?php

namespace App\Filament\Support\Concerns;

use Livewire\Component;

/**
 * Re-renders the admin sidebar after a resource record is saved, so the
 * navigation badges and group markers recalculate the current number of
 * records awaiting attention without a full page reload.
 *
 * Table and modal actions are covered globally in AppServiceProvider; this
 * trait covers the full-page create/edit forms, whose submit button calls the
 * Livewire `save()`/`create()` method directly instead of the action lifecycle.
 */
trait RefreshesNavigationBadges
{
    protected function refreshNavigationBadges(): void
    {
        if (! $this instanceof Component) {
            return;
        }

        $this->dispatch('refresh-sidebar');
    }

    protected function afterSave(): void
    {
        $this->refreshNavigationBadges();
    }

    protected function afterCreate(): void
    {
        $this->refreshNavigationBadges();
    }
}
