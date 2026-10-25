<?php

namespace App\Filament\Resources\WorkshopSessions\Schemas;

use App\Models\Masterclass;
use App\Models\WorkshopSession;
use Carbon\CarbonInterface;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class WorkshopSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Сеанс')->schema([
                Select::make('masterclass_id')
                    ->label('Мастер-класс')
                    ->relationship('masterclass', 'title')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, ?WorkshopSession $record): void {
                        self::fillFromMasterclass($state, $set, $record);
                    }),
                Select::make('master_id')->label('Ведущий мастер')->relationship('master', 'name')->searchable()->preload(),
                DateTimePicker::make('starts_at')
                    ->label('Дата и время (локально)')
                    ->timezone('Europe/Moscow')
                    ->seconds(false)
                    ->required()
                    ->minDate(fn (?WorkshopSession $record) => self::minimumStartDate($record)),
                TextInput::make('timezone')->label('Часовой пояс')->default('Europe/Moscow')->required(),
                TextInput::make('capacity')
                    ->label('Вместимость')
                    ->numeric()
                    ->integer()
                    ->minValue(fn (?WorkshopSession $record) => max(1, $record?->reservedSeats() ?? 0))
                    ->validationMessages([
                        'min' => 'Вместимость не может быть меньше числа уже записанных участников.',
                    ])
                    ->helperText('Подставляется из поля «Количество участников» карточки мастер-класса. Не может быть меньше уже занятых мест.')
                    ->default(8)
                    ->required(),
                TextInput::make('price_cents')
                    ->label('Цена за участника, ₽')
                    ->inputMode('decimal')
                    ->formatStateUsing(fn ($state) => $state === null || $state === '' ? null : number_format(((int) $state) / 100, 2, ',', ' '))
                    ->dehydrateStateUsing(function ($state): ?int {
                        if ($state === null || trim((string) $state) === '') {
                            return null;
                        }

                        $normalized = str_replace([' ', "\u{00A0}"], '', (string) $state);

                        return is_numeric(str_replace(',', '.', $normalized))
                            ? (int) round(((float) str_replace(',', '.', $normalized)) * 100)
                            : null;
                    })
                    ->rule('regex:/^\s*\d+(?:[ \x{00A0}]\d{3})*(?:[.,]\d{1,2})?\s*$/u')
                    ->validationMessages([
                        'regex' => 'Укажите сумму в рублях, например 1 500,00',
                    ])
                    ->helperText('Подставляется из поля «Цена в студии» карточки мастер-класса. Пусто — цена берётся из карточки.'),
                TextInput::make('location_name')->label('Место'),
                TextInput::make('address')->label('Адрес')->columnSpanFull(),
                Select::make('status')->label('Статус')->options(['scheduled' => 'Запланирован', 'cancelled' => 'Отменён'])->default('scheduled'),
                Toggle::make('is_published')->label('Опубликован')->default(true),
            ])->columns(2),
        ]);
    }

    /**
     * Подставляет вместимость и цену из карточки выбранного мастер-класса.
     * Некорректные значения не попадают в сеанс: менеджер получает уведомление
     * с перечнем полей карточки, которые нужно исправить.
     */
    /**
     * Минимальная дата сеанса: прошедшие дни недоступны, но существующий
     * прошедший сеанс можно открыть и отредактировать без потери даты.
     */
    private static function minimumStartDate(?WorkshopSession $record): CarbonInterface
    {
        $today = now('Europe/Moscow')->startOfDay();
        $current = $record?->starts_at?->copy()->timezone('Europe/Moscow')->startOfDay();

        return $current && $current->lessThan($today) ? $current : $today;
    }

    private static function fillFromMasterclass(mixed $state, Set $set, ?WorkshopSession $record): void
    {
        if (blank($state) || ! is_scalar($state)) {
            return;
        }

        $masterclass = Masterclass::query()->find((int) $state);

        if (! $masterclass) {
            Notification::make()
                ->danger()
                ->title('Мастер-класс не найден')
                ->body('Выберите мастер-класс из списка заново.')
                ->send();

            return;
        }

        $invalidFields = [];

        $capacity = $masterclass->included_participants;

        if (is_int($capacity) && $capacity >= 1) {
            $set('capacity', $capacity);

            if ($record !== null && $capacity < $record->reservedSeats()) {
                Notification::make()
                    ->warning()
                    ->title('Вместимость меньше числа записанных')
                    ->body('В новом мастер-классе указано '.$capacity.' мест, а в сеансе уже занято '.$record->reservedSeats().'. Увеличьте «Количество участников» в карточке или выберите другой мастер-класс.')
                    ->persistent()
                    ->send();
            }
        } else {
            $invalidFields[] = '«Количество участников»';
        }

        $priceCents = $masterclass->price_cents;

        if (is_int($priceCents) && $priceCents >= 0) {
            $set('price_cents', number_format($priceCents / 100, 2, ',', ' '));
        } else {
            $invalidFields[] = '«Цена в студии»';
        }

        if ($invalidFields === []) {
            return;
        }

        $title = strip_tags((string) $masterclass->title);

        Notification::make()
            ->warning()
            ->title('Проверьте карточку мастер-класса')
            ->body('В карточке «'.$title.'» исправьте: '.implode('; ', $invalidFields).'. Некорректные значения в сеанс не подставлены.')
            ->persistent()
            ->send();
    }
}
