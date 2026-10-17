<?php

namespace App\Filament\Widgets;

use App\Models\WorkshopSession;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingSessions extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkshopSession::query()
                    ->where('is_published', true)
                    ->where('starts_at', '>', now())
                    ->with(['masterclass', 'master'])
                    ->orderBy('starts_at')
                    ->limit(10)
            )
            ->heading('Предстоящие сеансы (из локальной базы)')
            ->columns([
                TextColumn::make('starts_at')->label('Дата и время')
                    ->formatStateUsing(fn ($state, $record) => $record->starts_at->copy()->setTimezone($record->timezone)->translatedFormat('d.m.Y H:i')),
                TextColumn::make('masterclass.title')->label('Мастер-класс')->wrap(),
                TextColumn::make('master.name')->label('Ведущий'),
                TextColumn::make('seats_left')->label('Свободно')->state(fn ($record) => $record->seatsLeft())->badge()->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->paginated(false);
    }
}
