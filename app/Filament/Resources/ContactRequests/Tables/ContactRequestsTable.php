<?php

namespace App\Filament\Resources\ContactRequests\Tables;

use App\Enums\ContactRequestForm;
use App\Enums\OutreachStatus;
use App\Models\ContactRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Номер')->searchable(),
                TextColumn::make('contact_name')->label('Имя')->searchable(),
                TextColumn::make('contact_phone')->label('Телефон'),
                TextColumn::make('contact_email')->label('Email')->toggleable(),
                TextColumn::make('message')->label('Сообщение')->limit(60)->wrap(),
                TextColumn::make('status')->label('Статус')->badge()->formatStateUsing(fn ($state) => $state->label()),
                IconColumn::make('consent_marketing')->label('Рассылка')->boolean()->toggleable(),
                TextColumn::make('source')->label('Источник')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Получено')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->groups([
                Group::make('form_source')
                    ->label('Источник')
                    ->titlePrefixedWithLabel(false)
                    ->getKeyFromRecordUsing(fn (ContactRequest $record) => $record->formSource()->value)
                    ->getTitleFromRecordUsing(fn (ContactRequest $record) => $record->formSource()->label())
                    ->scopeQueryByKeyUsing(function (Builder $query, ?string $key): Builder {
                        $form = filled($key) ? ContactRequestForm::tryFrom($key) : null;

                        return $form ? $query->forForm($form) : $query;
                    })
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderByRaw(
                        'case when message like ? then 1 else 0 end '.$direction,
                        [ContactRequest::MASTERCLASS_REQUEST_PREFIX.'%'],
                    )),
            ])
            ->defaultGroup('form_source')
            ->groupingSettingsHidden()
            ->extraAttributes(['class' => 'fi-grouped-requests'])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(collect(OutreachStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
