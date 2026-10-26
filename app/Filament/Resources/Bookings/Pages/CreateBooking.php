<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Support\Concerns\RefreshesNavigationBadges;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    use RefreshesNavigationBadges;

    protected static string $resource = BookingResource::class;
}
