<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Support\Concerns\RefreshesNavigationBadges;
use App\Models\Review;
use App\Services\ReviewModerationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditReview extends EditRecord
{
    use RefreshesNavigationBadges;

    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Смена статуса вручную из формы проходит те же последствия, что и кнопки
     * модерации: одобрение публикует видео, отклонение удаляет его.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Review $record */
        $previous = $record->status;

        $record = parent::handleRecordUpdate($record, $data);

        $status = $record->status;

        if ($status instanceof ReviewStatus && $previous !== $status) {
            app(ReviewModerationService::class)->applyStatus($record, $status, auth()->user());
        }

        return $record;
    }
}
