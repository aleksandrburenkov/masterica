<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Width;
use Illuminate\Support\Str;

/**
 * Режим редактирования HTML-кода для Filament RichEditor.
 *
 * Кнопка на панели инструментов открывает модальное окно с текущим HTML.
 * После применения код проверяется санитайзером и загружается обратно
 * в визуальный редактор через TipTap-команду setContent.
 */
class SourceCodeAction
{
    public static function make(): Action
    {
        return Action::make('sourceCode')
            ->label('HTML-код')
            ->modalHeading('Редактирование HTML-кода')
            ->modalDescription('Вставьте или отредактируйте HTML-разметку. После нажатия «Применить» код будет проверен и загружен в визуальный редактор.')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitActionLabel('Применить')
            ->fillForm(fn (array $arguments): array => [
                'html' => $arguments['html'] ?? '',
            ])
            ->schema([
                Textarea::make('html')
                    ->label('HTML-код документа')
                    ->rows(24)
                    ->required()
                    ->maxLength(500000)
                    ->columnSpanFull()
                    ->extraInputAttributes([
                        'spellcheck' => 'false',
                        'autocomplete' => 'off',
                        'style' => 'font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; line-height: 1.55;',
                    ]),
            ])
            ->action(function (array $data, RichEditor $component): void {
                $html = Str::sanitizeHtml((string) ($data['html'] ?? ''));

                $component->runCommands([
                    EditorCommand::make('setContent', arguments: [$html]),
                ]);
            });
    }
}
