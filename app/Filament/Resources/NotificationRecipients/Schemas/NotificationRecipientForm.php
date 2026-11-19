<?php

namespace App\Filament\Resources\NotificationRecipients\Schemas;

use App\Enums\UserRole;
use App\Models\NotificationRecipient;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotificationRecipientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Получатель писем')
                ->description('Выберите существующего пользователя. Письма о новых обращениях будут приходить на email из его профиля.')
                ->schema([
                    Select::make('user_id')
                        ->label('Пользователь')
                        ->options(fn (): array => User::query()
                            ->where('role', '!=', UserRole::Owner->value)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [
                                $user->id => $user->name.' — '.$user->email.' ('.$user->role->label().')',
                            ])
                            ->all())
                        ->searchable()
                        ->required()
                        ->unique(NotificationRecipient::class, 'user_id', ignoreRecord: true)
                        ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            $user = User::query()->find($value);

                            if ($user === null) {
                                $fail('Выбранный пользователь не найден.');

                                return;
                            }

                            if (filter_var((string) $user->email, FILTER_VALIDATE_EMAIL) === false) {
                                $fail('У пользователя некорректный email. Исправьте email в профиле пользователя.');
                            }
                        })
                        ->helperText('Владелец получает письма автоматически и в этот список не добавляется.')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
