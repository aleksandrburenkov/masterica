<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Номер')->searchable(),
                TextColumn::make('client')
                    ->label('Клиент')
                    ->state(fn (Booking $record) => $record->user?->name ?? $record->contact_name)
                    ->description(fn (Booking $record) => $record->user
                        ? $record->user->email
                        : 'Гость · '.$record->contact_email)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->where('contact_name', 'like', "%{$search}%")
                                ->orWhere('contact_email', 'like', "%{$search}%")
                                ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%"));
                        });
                    }),
                TextColumn::make('session.masterclass.title')->label('Мастер-класс')->wrap(),
                TextColumn::make('session.starts_at')->label('Сеанс')->dateTime('d.m.Y H:i')->timezone('Europe/Moscow')->sortable(),
                TextColumn::make('status')->label('Запись')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('payment_status')->label('Оплата')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('participants_count')->label('Чел.'),
                TextColumn::make('total_cents')->label('Сумма')->money('RUB', divideBy: 100),
                IconColumn::make('consent_marketing')->label('Рассылка')->boolean()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(collect(BookingStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
                SelectFilter::make('payment_status')->label('Оплата')->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Booking $record) => $record->status === BookingStatus::Held || $record->status === BookingStatus::PendingPayment)
                    ->action(fn (Booking $record) => app(BookingService::class)->confirm($record, 'Подтверждено администратором')),
                Action::make('attend')
                    ->label('Посещена')
                    ->color('success')
                    ->visible(fn (Booking $record) => $record->status === BookingStatus::Confirmed)
                    ->action(fn (Booking $record) => $record->forceFill(['status' => BookingStatus::Attended, 'attended_at' => now()])->save()),
                Action::make('cancel')
                    ->label('Отменить')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => $record->status->isActive())
                    ->action(fn (Booking $record) => app(BookingService::class)->cancel($record, 'Отменено администратором')),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
