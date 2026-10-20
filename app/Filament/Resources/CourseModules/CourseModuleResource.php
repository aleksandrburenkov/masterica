<?php

namespace App\Filament\Resources\CourseModules;

use App\Filament\Resources\CourseModules\Pages\CreateCourseModule;
use App\Filament\Resources\CourseModules\Pages\EditCourseModule;
use App\Filament\Resources\CourseModules\Pages\ListCourseModules;
use App\Filament\Resources\CourseModules\Schemas\CourseModuleForm;
use App\Filament\Resources\CourseModules\Tables\CourseModulesTable;
use App\Models\CourseModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CourseModuleResource extends Resource
{
    protected static ?string $model = CourseModule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Блок курсы';

    protected static ?string $navigationLabel = 'Модули курсов';

    protected static ?string $modelLabel = 'Модуль';

    protected static ?string $pluralModelLabel = 'Модули курсов';

    public static function form(Schema $schema): Schema
    {
        return CourseModuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseModulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseModules::route('/'),
            'create' => CreateCourseModule::route('/create'),
            'edit' => EditCourseModule::route('/{record}/edit'),
        ];
    }
}
