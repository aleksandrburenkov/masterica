<?php

namespace App\Filament\Resources\CourseEnrollments\Tables;

use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use App\Services\CourseService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourseEnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')->label('Пользователь')->searchable(),
                TextColumn::make('course.title')->label('Курс')->wrap(),
                TextColumn::make('status')->label('Доступ')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('payment_status')->label('Оплата')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('granted_at')->label('Выдан')->dateTime('d.m.Y')->toggleable(),
                TextColumn::make('expires_at')->label('Истекает')->dateTime('d.m.Y')->toggleable(),
                TextColumn::make('progress')->label('Прогресс')->state(fn (CourseEnrollment $record) => app(CourseService::class)->progressPercent($record).'%'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(collect(EnrollmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
            ])
            ->recordActions([
                Action::make('grantTestPayment')
                    ->label('Отметить тестовую оплату')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Открыть доступ (тестовая оплата)?')
                    ->modalDescription('Локальное действие: реальное списание не выполняется.')
                    ->visible(fn (CourseEnrollment $record) => $record->status !== EnrollmentStatus::Active)
                    ->action(fn (CourseEnrollment $record) => app(CourseService::class)->grantTestPayment($record->user, $record->course, auth()->user())),
                Action::make('revoke')
                    ->label('Отозвать доступ')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([TextInput::make('reason')->label('Причина')->required()])
                    ->visible(fn (CourseEnrollment $record) => $record->status !== EnrollmentStatus::Revoked)
                    ->action(fn (CourseEnrollment $record, array $data) => app(CourseService::class)->revoke($record, auth()->user(), $data['reason'])),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
