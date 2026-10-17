<?php

namespace App\Filament\Resources\OutreachRequests\Tables;

use App\Enums\OutreachStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OutreachRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Номер')->searchable(),
                TextColumn::make('direction')->label('Направление')->wrap(),
                TextColumn::make('requested_date')->label('Дата')->date('d.m.Y')->sortable(),
                TextColumn::make('location')->label('Место')->toggleable(),
                TextColumn::make('participants_count')->label('Чел.'),
                TextColumn::make('calc_total_cents')->label('Расчёт')->money('RUB', divideBy: 100),
                TextColumn::make('status')->label('Статус')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('contact_phone')->label('Телефон')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(collect(OutreachStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
