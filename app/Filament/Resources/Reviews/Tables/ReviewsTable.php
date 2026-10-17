<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Enums\ReviewStatus;
use App\Models\Review;
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
                    ->visible(fn (Review $record) => $record->status !== ReviewStatus::Approved)
                    ->action(fn (Review $record) => $record->forceFill([
                        'status' => ReviewStatus::Approved,
                        'published_at' => $record->published_at ?? now(),
                        'moderated_by' => auth()->id(),
                        'moderated_at' => now(),
                    ])->save()),
                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Review $record) => $record->status !== ReviewStatus::Rejected)
                    ->action(fn (Review $record) => $record->forceFill([
                        'status' => ReviewStatus::Rejected,
                        'moderated_by' => auth()->id(),
                        'moderated_at' => now(),
                    ])->save()),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
