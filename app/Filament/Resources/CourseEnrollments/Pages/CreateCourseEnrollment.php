<?php

namespace App\Filament\Resources\CourseEnrollments\Pages;

use App\Filament\Resources\CourseEnrollments\CourseEnrollmentResource;
use App\Filament\Support\Concerns\RefreshesNavigationBadges;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseEnrollment extends CreateRecord
{
    use RefreshesNavigationBadges;

    protected static string $resource = CourseEnrollmentResource::class;
}
