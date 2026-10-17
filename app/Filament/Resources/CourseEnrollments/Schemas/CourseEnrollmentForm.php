<?php

namespace App\Filament\Resources\CourseEnrollments\Schemas;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseEnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Доступ к курсу')->schema([
                Select::make('user_id')->label('Пользователь')->relationship('user', 'email')->searchable()->preload()->required(),
                Select::make('course_id')->label('Курс')->relationship('course', 'title')->searchable()->preload()->required(),
                Select::make('status')->label('Статус доступа')->options(collect(EnrollmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())->required(),
                Select::make('payment_status')->label('Оплата')->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())->required(),
                DateTimePicker::make('granted_at')->label('Выдан'),
                DateTimePicker::make('expires_at')->label('Истекает'),
                DateTimePicker::make('completed_at')->label('Завершён'),
                DateTimePicker::make('revoked_at')->label('Отозван'),
            ])->columns(2),
        ]);
    }
}
