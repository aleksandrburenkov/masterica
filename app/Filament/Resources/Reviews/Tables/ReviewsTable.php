<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Services\ReviewModerationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Автор')->searchable(),
                TextColumn::make('type')->label('Тип')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('rating')->label('Оценка'),
                TextColumn::make('body')->label('Текст')->limit(70)->wrap(),
                TextColumn::make('video.scan_status')
                    ->label('Проверка видео')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state?->value) {
                        'clean' => 'success',
                        'suspicious', 'infected' => 'danger',
                        'error' => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('is_demo')->label('Demo')->boolean(),
                TextColumn::make('status')->label('Статус')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('published_at')->label('Опубликован')->dateTime('d.m.Y')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(collect(ReviewStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
                TernaryFilter::make('is_demo')->label('Демонстрационные'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Опубликовать')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation(fn (Review $record): bool => $record->video()->exists())
                    ->modalDescription('Видео будет опубликовано и при необходимости оптимизировано.')
                    ->visible(fn (Review $record) => $record->status !== ReviewStatus::Approved)
                    ->action(fn (Review $record) => app(ReviewModerationService::class)->approve($record, auth()->user())),
                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation(fn (Review $record): bool => $record->video()->exists())
                    ->modalDescription('Видео будет удалено без возможности восстановления. Текст отзыва сохранится.')
                    ->visible(fn (Review $record) => $record->status !== ReviewStatus::Rejected)
                    ->action(fn (Review $record) => app(ReviewModerationService::class)->reject($record, auth()->user())),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
