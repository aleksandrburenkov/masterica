<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Номер')->searchable(),
                TextColumn::make('user.email')->label('Клиент')->searchable(),
                TextColumn::make('items_count')->label('Позиций')->counts('items'),
                TextColumn::make('total_cents')->label('Сумма')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('status')->label('Статус')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('payment_status')->label('Оплата')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('delivery_method')->label('Получение')->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('placed_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
                SelectFilter::make('payment_status')->label('Оплата')->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Отметить тестовую оплату')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Отметить тестовую оплату?')
                    ->modalDescription('Локальное действие: реальное списание не выполняется.')
                    ->visible(fn (Order $record) => $record->payment_status !== PaymentStatus::Paid)
                    ->action(fn (Order $record) => app(OrderService::class)->markPaid($record, auth()->user())),
                Action::make('complete')
                    ->label('Выполнить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => ! in_array($record->status, [OrderStatus::Completed, OrderStatus::Cancelled], true))
                    ->action(fn (Order $record) => app(OrderService::class)->complete($record, auth()->user())),
                Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => ! in_array($record->status, [OrderStatus::Completed, OrderStatus::Cancelled], true))
                    ->action(fn (Order $record) => app(OrderService::class)->cancel($record, 'Отменено администратором', auth()->user())),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
