<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\WorkshopSession;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudioStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $upcoming = WorkshopSession::query()
            ->where('is_published', true)
            ->where('starts_at', '>', now())
            ->count();

        $paidRevenue = Order::query()->where('payment_status', 'paid')->sum('total_cents');
        $activeEnrollments = CourseEnrollment::query()->where('status', 'active')->count();

        return [
            Stat::make('Предстоящие сеансы', (string) $upcoming)
                ->description('Опубликованные сеансы в будущем')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('info'),
            Stat::make('Записи на занятия', (string) Booking::query()->count())
                ->description('Всего записей в системе')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('success'),
            Stat::make('Заказы', (string) Order::query()->count())
                ->description('Оформлено заказов')
                ->icon(Heroicon::OutlinedShoppingCart),
            Stat::make('Оплачено по заказам', money((int) $paidRevenue))
                ->description('Сумма оплаченных заказов')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('Активные доступы к курсам', (string) $activeEnrollments)
                ->description('Открытые доступы')
                ->icon(Heroicon::OutlinedAcademicCap)
                ->color('warning'),
        ];
    }
}
