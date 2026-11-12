<?php

namespace App\Filament\Pages;

use App\Models\SitePage;
use App\Support\Settings;
use App\Support\SitePages;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Управление активностью страниц сайта. Список строится автоматически из
 * общего реестра `site_pages`: статические страницы маршрутов, материалы
 * (ContentPage) и правовые документы (LegalDocument). Неактивные страницы
 * показывают посетителю страницу «на техническом обслуживании» с общим,
 * редактируемым здесь текстом.
 */
class PagesSettings extends Page implements HasTable
{
    use InteractsWithTable;

    /** @var array<string, mixed> */
    public ?array $data = [];

    protected string $view = 'filament.pages.pages-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Страницы сайта';

    protected static ?string $title = 'Страницы сайта';

    protected static ?int $navigationSort = 101;

    public static function canAccess(): bool
    {
        return auth()->user()?->role?->canManageSettings() ?? false;
    }

    public function mount(): void
    {
        // Гарантируем, что статические страницы из реестра присутствуют в списке.
        SitePages::syncStatic();

        $this->form->fill([
            'maintenance_enabled' => Settings::bool('pages.maintenance_enabled'),
            'maintenance_text' => setting('pages.maintenance_text', ''),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Глобальный режим модернизации')
                ->description('Одним переключателем скрывает содержимое всех страниц сайта от посетителей. Действует на весь сайт сразу, без настройки отдельных страниц. Владелец сайта, авторизованный в админ-панели, видит сайт как обычно.')
                ->schema([
                    Toggle::make('maintenance_enabled')
                        ->label('Скрыть весь сайт от посетителей')
                        ->helperText('Включите на время обновлений и правок на продакшене. Посетители увидят страницу с текстом ниже.')
                        ->onColor('danger')
                        ->offColor('success')
                        ->columnSpanFull(),
                ]),
            Section::make('Страница на техническом обслуживании')
                ->description('Этот текст видят посетители неактивных страниц. Изменение применяется сразу ко всем неактивным страницам.')
                ->schema([
                    Textarea::make('maintenance_text')
                        ->label('Текст для неактивных страниц')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Settings::set('pages.maintenance_enabled', ($state['maintenance_enabled'] ?? false) ? '1' : '0', 'pages', 'bool');
        Settings::set('pages.maintenance_text', (string) ($state['maintenance_text'] ?? ''), 'pages', 'text');

        Notification::make()->title('Настройки страниц сохранены')->success()->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Страница')->searchable()->wrap(),
                TextColumn::make('group')->label('Раздел')->badge()->toggleable(),
                TextColumn::make('path')
                    ->label('Адрес')
                    ->state(fn (SitePage $record): string => $record->path())
                    ->url(fn (SitePage $record): ?string => $record->url() ?: null, shouldOpenInNewTab: true)
                    ->toggleable(),
                ToggleColumn::make('is_active')
                    ->label('Активна')
                    ->onColor('success')
                    ->offColor('danger'),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label('Раздел')
                    ->options(fn (): array => SitePage::query()
                        ->distinct()
                        ->orderBy('group')
                        ->pluck('group', 'group')
                        ->all()),
            ])
            ->defaultSort('sort')
            ->paginated(false);
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
            Section::make('Все страницы сайта')
                ->description('Список формируется автоматически: страницы маршрутов, материалы и правовые документы. Новые страницы появляются сразу.')
                ->schema([
                    EmbeddedTable::make(),
                ]),
        ]);
    }

    /** @return array<int, Action> */
    public function getFormActions(): array
    {
        return [
            Action::make('save')->label('Сохранить текст')->submit('save')->color('primary'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return SitePage::query();
    }
}
