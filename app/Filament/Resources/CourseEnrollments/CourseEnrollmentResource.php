<?php

namespace App\Filament\Resources\CourseEnrollments;

use App\Filament\Resources\CourseEnrollments\Pages\CreateCourseEnrollment;
use App\Filament\Resources\CourseEnrollments\Pages\EditCourseEnrollment;
use App\Filament\Resources\CourseEnrollments\Pages\ListCourseEnrollments;
use App\Filament\Resources\CourseEnrollments\Schemas\CourseEnrollmentForm;
use App\Filament\Resources\CourseEnrollments\Tables\CourseEnrollmentsTable;
use App\Models\CourseEnrollment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CourseEnrollmentResource extends Resource
{
    protected static ?string $model = CourseEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Блок курсы';

    protected static ?string $navigationLabel = 'Доступы к курсам';

    protected static ?string $modelLabel = 'Доступ к курсу';

    protected static ?string $pluralModelLabel = 'Доступы к курсам';

    public static function form(Schema $schema): Schema
    {
        return CourseEnrollmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseEnrollmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseEnrollments::route('/'),
            'create' => CreateCourseEnrollment::route('/create'),
            'edit' => EditCourseEnrollment::route('/{record}/edit'),
        ];
    }
}
