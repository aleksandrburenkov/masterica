<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Support\Concerns\RefreshesNavigationBadges;
use Filament\Resources\Pages\CreateRecord;

class CreateReview extends CreateRecord
{
    use RefreshesNavigationBadges;

    protected static string $resource = ReviewResource::class;
}
