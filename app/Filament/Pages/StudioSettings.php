<?php

namespace App\Filament\Pages;

use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Editable studio settings. Prices and rules live here (and in the
 * `settings` table) instead of being hard-coded into templates.
 */
class StudioSettings extends Page
{
    /** @var array<string, mixed> */
    public ?array $data = [];

    protected string $view = 'filament.pages.studio-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Настройки студии';

    protected static ?int $navigationSort = 100;

    public static function canAccess(): bool
    {
        return auth()->user()?->role?->canManageSettings() ?? false;
    }

    public function mount(): void
    {
        $data = [];
        foreach (self::fields() as $name => $meta) {
            $data[$name] = setting($meta['key'], '');
        }
        $this->form->fill($data);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $studio = [];
        $pricing = [];
        $rules = [];

        foreach (self::fields() as $name => $meta) {
            $component = $meta['textarea']
                ? Textarea::make($name)->label($meta['label'])->rows(2)
                : TextInput::make($name)->label($meta['label']);

            match ($meta['group']) {
                'pricing' => $pricing[] = $component,
                'rules' => $rules[] = $component,
                default => $studio[] = $component,
            };
        }

        return $schema->components([
            Section::make('Студия и контакты')->schema($studio)->columns(2),
            Section::make('Цены (в рублях и копейках хранения)')->schema($pricing)->columns(2),
            Section::make('Правила')->schema($rules)->columns(2),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (self::fields() as $name => $meta) {
            Settings::set($meta['key'], (string) ($state[$name] ?? ''), $meta['group'], $meta['type']);
        }

        Notification::make()->title('Настройки сохранены')->success()->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions()),
                ]),
        ]);
    }

    /** @return array<int, Action> */
    public function getFormActions(): array
    {
        return [
            Action::make('save')->label('Сохранить')->submit('save')->color('primary'),
        ];
    }

    /**
     * Field map: form field name => setting key and metadata.
     *
     * @return array<string, array{key:string,label:string,group:string,type:string,textarea?:bool}>
     */
    private static function fields(): array
    {
        $map = [
            'name' => ['studio.name', 'Название студии'],
            'tagline' => ['studio.tagline', 'Слоган'],
            'city' => ['studio.city', 'Город'],
            'address' => ['studio.address', 'Адрес'],
            'phone' => ['studio.phone', 'Телефон'],
            'hours' => ['studio.hours', 'Часы работы'],
            'email' => ['studio.email', 'Электронная почта'],
            'timezone' => ['studio.timezone', 'Часовой пояс'],
            'og_description' => ['studio.og_description', 'Описание для SEO (OG)', true],
            'legal_note' => ['studio.legal_note', 'Примечание (демо-документы)', true],

            'workshop_price_cents' => ['pricing.workshop_price_cents', 'Обычный МК: цена, коп.'],
            'workshop_duration_minutes' => ['pricing.workshop_duration_minutes', 'Обычный МК: длительность, мин'],
            'workshop_capacity' => ['pricing.workshop_capacity', 'Обычный МК: мест в сеансе'],
            'outreach_base_cents' => ['pricing.outreach_base_cents', 'Выезд: база, коп.'],
            'outreach_included' => ['pricing.outreach_included', 'Выезд: участников включено'],
            'outreach_extra_cents' => ['pricing.outreach_extra_cents', 'Выезд: доп. участник, коп.'],
            'outreach_duration_minutes' => ['pricing.outreach_duration_minutes', 'Выезд: длительность, мин'],
            'delivery_pickup_cents' => ['pricing.delivery_pickup_cents', 'Самовывоз, коп.'],
            'delivery_city_cents' => ['pricing.delivery_city_cents', 'Доставка по городу, коп.'],

            'currency' => ['rules.currency', 'Валюта (ISO)'],
            'hold_minutes' => ['rules.hold_minutes', 'Резерв места, мин'],
            'free_cancel_hours' => ['rules.free_cancel_hours', 'Бесплатная отмена, часов'],
            'delivery_prep_days' => ['rules.delivery_prep_days', 'Срок подготовки заказа, дней'],
            'cancel_policy' => ['rules.cancel_policy', 'Правило отмены', true],
            'payment_note' => ['rules.payment_note', 'Примечание об оплате', true],
            'materials_note' => ['rules.materials_note', 'Материалы', true],
        ];

        $fields = [];
        foreach ($map as $name => $info) {
            [$key, $label] = $info;
            $fields[$name] = [
                'key' => $key,
                'label' => $label,
                'group' => explode('.', $key)[0],
                'type' => str_ends_with($key, '_cents') || str_ends_with($key, '_minutes') || str_ends_with($key, '_hours') || str_ends_with($key, '_days') || str_ends_with($key, '_included') || str_ends_with($key, '_capacity') ? 'int' : 'string',
                'textarea' => (bool) ($info[2] ?? false),
            ];
        }

        return $fields;
    }
}
