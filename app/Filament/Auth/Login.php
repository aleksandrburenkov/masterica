<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;

class Login extends BaseLogin
{
    /**
     * The default Filament hint is replaced by a full-width link below the form,
     * so the link stays visible on mobile screens too.
     */
    protected function getPasswordFormComponent(): Component
    {
        $component = parent::getPasswordFormComponent();

        $component->hint(null);

        return $component;
    }
}
