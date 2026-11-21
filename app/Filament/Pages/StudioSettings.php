<?php

namespace App\Filament\Pages;

use App\Support\Settings;
use App\Support\SiteCache;
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
use Illuminate\Support\Facades\Log;
use Throwable;
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
        $rules = [];
        $notifications = [];

        foreach (self::fields() as $name => $meta) {
            if ($meta['kind'] === 'latitude' || $meta['kind'] === 'longitude') {
                $limit = $meta['kind'] === 'latitude' ? 90 : 180;

                $component = TextInput::make($name)
                    ->label($meta['label'])
                    ->inputMode('decimal')
                    ->helperText('Используется для карты на «Контактах» и для расчёта удалённости выездных мастер-классов. Десятичный разделитель — точка, например 53.243600')
                    ->nullable()
                    ->numeric()
                    ->rule('between:-'.$limit.','.$limit)
                    ->validationMessages([
                        'numeric' => 'Укажите координату числом',
                        'between' => 'Координата должна быть от -'.$limit.' до '.$limit,
                    ]);
            } elseif ($meta['kind'] === 'zoom') {
                $component = TextInput::make($name)
                    ->label($meta['label'])
                    ->inputMode('numeric')
                    ->helperText('Масштаб от 1 до 19: чем больше значение, тем ближе карта.')
                    ->nullable()
                    ->integer()
                    ->rule('between:1,19')
                    ->validationMessages([
                        'integer' => 'Укажите целое число',
                        'between' => 'Масштаб должен быть от 1 до 19',
                    ]);
            } elseif ($meta['money']) {
                $component = TextInput::make($name)
                    ->label($meta['label'])
                    ->inputMode('decimal')
                    ->formatStateUsing(fn ($state) => self::formatMoneyState($state))
                    ->dehydrateStateUsing(fn ($state) => self::parseMoneyState($state))
                    ->rule('regex:/^\d+(?:[ \x{00A0}]\d{3})*(?:[.,]\d{1,2})?$/u')
                    ->validationMessages([
                        'regex' => 'Укажите сумму в рублях, например 1 500,00',
                    ]);
            } elseif ($meta['kind'] === 'email') {
                $component = TextInput::make($name)
                    ->label($meta['label'])
                    ->email()
                    ->maxLength(160)
                    ->nullable()
                    ->helperText('Если адрес заполнен и корректен, письма о новых обращениях приходят на него вместо email владельца. Пустое или некорректное значение — используется email пользователя с ролью «Владелец».');
            } else {
                $component = $meta['textarea']
                    ? Textarea::make($name)->label($meta['label'])->rows(2)
                    : TextInput::make($name)->label($meta['label']);
            }

            match ($meta['group']) {
                'rules' => $rules[] = $component,
                'notifications' => $notifications[] = $component,
                default => $studio[] = $component,
            };
        }

        return $schema->components([
            Section::make('Студия и контакты')->schema($studio)->columns(2),
            Section::make('Почтовые уведомления о новых обращениях')
                ->description('Письма приходят владельцу автоматически. Дополнительных получателей владелец назначает в разделе «Рассылка обращений».')
                ->schema($notifications)
                ->columns(2),
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
            Section::make('Кеш сайта')
                ->description('Кеш ускоряет сайт, но после правок может показывать старые данные. Сбросьте нужную группу, чтобы изменения появились сразу, не дожидаясь окончания срока хранения кеша. Данные сайта, товары, мастер-классы, заказы и записи клиентов при этом не удаляются.')
                ->schema([
                    Actions::make($this->getDataCacheActions())->fullWidth(),
                ]),
            Section::make('Служебные кеши')
                ->description('Скомпилированные шаблоны, конфигурация, маршруты, события и компоненты админ-панели. Сбрасывайте после обновления файлов сайта, установки пакетов или изменения настроек окружения.')
                ->schema([
                    Actions::make($this->getFrameworkCacheActions())->fullWidth(),
                ]),
            Section::make('Полный сброс')
                ->description('Очищает сразу все кеши, перечисленные на этой странице. Используйте после крупных обновлений, если не уверены, какой именно кеш устарел.')
                ->schema([
                    Actions::make($this->getFullResetActions())->fullWidth(),
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

    /** @return array<int, Action> */
    public function getDataCacheActions(): array
    {
        $actions = [
            Action::make('clearContentCache')
                ->label('Контент и настройки')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Сбросить кеш контента?')
                ->modalDescription('Настройки, правовые документы, адреса и общий кеш приложения будут загружены из базы заново. Данные сайта не удаляются.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearContentCache()),
            Action::make('clearStudioSettingsCache')
                ->label('Настройки студии')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->requiresConfirmation()
                ->modalHeading('Сбросить кеш настроек студии?')
                ->modalDescription('При следующем запросе настройки будут прочитаны из базы данных.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearStudioSettingsCache()),
            Action::make('clearLegalDocumentsCache')
                ->label('Правовые документы')
                ->icon(Heroicon::OutlinedDocumentText)
                ->requiresConfirmation()
                ->modalHeading('Сбросить кеш правовых документов?')
                ->modalDescription('При следующем запросе документы будут прочитаны из базы данных.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearLegalDocumentsCache()),
        ];

        if (SiteCache::canClearGeocoding()) {
            $actions[] = Action::make('clearGeocodingCache')
                ->label('Адреса на карте')
                ->icon(Heroicon::OutlinedMapPin)
                ->requiresConfirmation()
                ->modalHeading('Сбросить кеш адресов?')
                ->modalDescription('При следующем расчёте удалённости координаты адресов будут запрошены у геокодера заново.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearGeocodingCache());
        }

        return $actions;
    }

    /** @return array<int, Action> */
    public function getFrameworkCacheActions(): array
    {
        $actions = [
            Action::make('clearViewCache')
                ->label('Шаблоны')
                ->icon(Heroicon::OutlinedPaintBrush)
                ->requiresConfirmation()
                ->modalHeading('Сбросить кеш шаблонов?')
                ->modalDescription('Скомпилированные Blade-шаблоны будут удалены и собраны заново при первом открытии страницы.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearViewCache()),
            Action::make('clearConfigCache')
                ->label('Конфигурация')
                ->icon(Heroicon::OutlinedBolt)
                ->requiresConfirmation()
                ->modalHeading('Сбросить кеш конфигурации?')
                ->modalDescription('Файл конфигурации будет удалён. Следующий запрос прочитает актуальные значения из файлов настроек и окружения.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearConfigCache()),
            Action::make('clearRouteCache')
                ->label('Маршруты')
                ->icon(Heroicon::OutlinedCodeBracket)
                ->requiresConfirmation()
                ->modalHeading('Сбросить кеш маршрутов?')
                ->modalDescription('Список маршрутов будет собран заново при следующем запросе.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearRouteCache()),
            Action::make('clearEventCache')
                ->label('События')
                ->icon(Heroicon::OutlinedQueueList)
                ->requiresConfirmation()
                ->modalHeading('Сбросить кеш событий?')
                ->modalDescription('Обнаруженные обработчики событий будут собраны заново при следующем запросе.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearEventCache()),
            Action::make('clearFilamentCache')
                ->label('Компоненты админки')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->requiresConfirmation()
                ->modalHeading('Сбросить кеш компонентов админ-панели?')
                ->modalDescription('Кеш компонентов Filament и манифест иконок будут удалены и собраны заново при следующем открытии админ-панели.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearFilamentCache()),
        ];

        if (function_exists('opcache_reset')) {
            $actions[] = Action::make('clearPhpCache')
                ->label('PHP OPcache')
                ->icon(Heroicon::OutlinedServerStack)
                ->requiresConfirmation()
                ->modalHeading('Сбросить PHP OPcache?')
                ->modalDescription('Скомпилированный PHP-код будет перечитан с диска. Помогает после обновления файлов сайта.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearPhpCache());
        }

        return $actions;
    }

    /** @return array<int, Action> */
    public function getFullResetActions(): array
    {
        return [
            Action::make('clearAllCache')
                ->label('Сбросить весь кеш')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Сбросить весь кеш сайта?')
                ->modalDescription('Будут очищены кеш приложения, настройки, документы, адреса, шаблоны, конфигурация, маршруты, события и компоненты админ-панели. Данные сайта не удаляются.')
                ->visible(fn (): bool => $this->canManageCache())
                ->action(fn () => $this->clearEverything()),
        ];
    }

    public function clearContentCache(): void
    {
        $this->runCacheClear(
            'Кеш контента и настроек',
            'Настройки, правовые документы, адреса и общий кеш приложения очищены. При следующем запросе данные загрузятся заново.',
            fn () => SiteCache::clearData(),
        );
    }

    public function clearStudioSettingsCache(): void
    {
        $this->runCacheClear(
            'Кеш настроек студии',
            'Настройки будут прочитаны из базы данных при следующем запросе.',
            fn () => SiteCache::clearSettings(),
        );
    }

    public function clearLegalDocumentsCache(): void
    {
        $this->runCacheClear(
            'Кеш правовых документов',
            'Правовые документы будут прочитаны из базы данных при следующем запросе.',
            fn () => SiteCache::clearLegalDocuments(),
        );
    }

    public function clearGeocodingCache(): void
    {
        $this->runCacheClear(
            'Кеш адресов на карте',
            'Кеш геокодера очищен.',
            function (): string {
                $deleted = SiteCache::clearGeocoding();

                return $deleted > 0
                    ? 'Удалено записей: '.$deleted.'.'
                    : 'Записей в кеше адресов не было.';
            },
        );
    }

    public function clearViewCache(): void
    {
        $this->runCacheClear(
            'Кеш шаблонов',
            'Скомпилированные Blade-шаблоны удалены. Они соберутся заново при первом открытии страницы.',
            fn () => SiteCache::clearViews(),
        );
    }

    public function clearConfigCache(): void
    {
        $this->runCacheClear(
            'Кеш конфигурации',
            'Файл конфигурации удалён. Следующий запрос прочитает актуальные настройки.',
            fn () => SiteCache::clearConfig(),
        );
    }

    public function clearRouteCache(): void
    {
        $this->runCacheClear(
            'Кеш маршрутов',
            'Список маршрутов будет собран заново при следующем запросе.',
            fn () => SiteCache::clearRoutes(),
        );
    }

    public function clearEventCache(): void
    {
        $this->runCacheClear(
            'Кеш событий',
            'Обработчики событий будут обнаружены заново при следующем запросе.',
            fn () => SiteCache::clearEvents(),
        );
    }

    public function clearFilamentCache(): void
    {
        $this->runCacheClear(
            'Кеш компонентов админ-панели',
            'Компоненты Filament и манифест иконок будут собраны заново при следующем открытии админ-панели.',
            fn () => SiteCache::clearFilament(),
        );
    }

    public function clearPhpCache(): void
    {
        if (! $this->canManageCache()) {
            abort(403);
        }

        if (! SiteCache::clearOpcache()) {
            Notification::make()
                ->title('PHP OPcache недоступен')
                ->body('На этом сервере OPcache отключён или сброс недоступен. Остальные кеши можно очистить отдельными кнопками.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('PHP OPcache сброшен')
            ->success()
            ->send();

        Log::info('PHP OPcache сброшен вручную', ['user_id' => auth()->id()]);
    }

    public function clearEverything(): void
    {
        $this->runCacheClear(
            'Полный сброс кеша',
            'Все кеши очищены. Сайт и админ-панель при следующем открытии соберут их заново.',
            fn () => SiteCache::clearAll(),
        );
    }

    /** @param callable():?string $callback */
    private function runCacheClear(string $title, string $fallbackMessage, callable $callback): void
    {
        if (! $this->canManageCache()) {
            abort(403);
        }

        try {
            $message = $callback();

            Notification::make()
                ->title($title.' — готово')
                ->body(is_string($message) && $message !== '' ? $message : $fallbackMessage)
                ->success()
                ->send();

            Log::info('Кеш сайта сброшен вручную', [
                'user_id' => auth()->id(),
                'cache_group' => $title,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Не удалось сбросить кеш')
                ->body($title.': '.$exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function canManageCache(): bool
    {
        return auth()->user()?->role?->canManageSettings() ?? false;
    }

    /**
     * Field map: form field name => setting key and metadata.
     *
     * @return array<string, array{key:string,label:string,group:string,type:string,textarea?:bool,money:bool,kind?:string}>
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
            'latitude' => ['studio.latitude', 'Широта на карте', false, 'latitude'],
            'longitude' => ['studio.longitude', 'Долгота на карте', false, 'longitude'],
            'map_zoom' => ['studio.map_zoom', 'Масштаб карты', false, 'zoom'],
            'og_description' => ['studio.og_description', 'Описание для SEO (OG)', true],
            'legal_note' => ['studio.legal_note', 'Примечание в футере', true],

            'alternate_owner_email' => ['notifications.alternate_owner_email', 'Альтернативный email владельца', false, 'email'],

            'currency' => ['rules.currency', 'Валюта (ISO)'],
            'hold_minutes' => ['rules.hold_minutes', 'Резерв места, мин'],
            'free_cancel_hours' => ['rules.free_cancel_hours', 'Бесплатная отмена, часов'],
            'delivery_prep_days' => ['rules.delivery_prep_days', 'Срок подготовки заказа, дней'],
            'delivery_city_cents' => ['rules.delivery_city_cents', 'Доставка по городу, ₽'],
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
                'type' => str_ends_with($key, '_cents') || str_ends_with($key, '_minutes') || str_ends_with($key, '_hours') || str_ends_with($key, '_days') || str_ends_with($key, '_included') || str_ends_with($key, '_zoom') ? 'int' : 'string',
                'textarea' => (bool) ($info[2] ?? false),
                'money' => str_ends_with($key, '_cents'),
                'kind' => $info[3] ?? null,
            ];
        }

        return $fields;
    }

    private static function formatMoneyState(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        return number_format(((int) $state) / 100, 2, ',', ' ');
    }

    private static function parseMoneyState(mixed $state): int
    {
        $value = str_replace([' ', "\u{00A0}"], '', (string) $state);
        $value = str_replace(',', '.', $value);

        return (int) round(((float) $value) * 100);
    }
}
