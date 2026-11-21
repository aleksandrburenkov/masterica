@php($resetUrl = filament()->getRequestPasswordResetUrl())

@if (filled($resetUrl))
    <div style="margin-top: 1.25rem; text-align: center;">
        <x-filament::link :href="$resetUrl" tabindex="-1">
            Забыли пароль?
        </x-filament::link>
    </div>
@endif
