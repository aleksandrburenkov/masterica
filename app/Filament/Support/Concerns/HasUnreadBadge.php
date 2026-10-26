<?php

namespace App\Filament\Support\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds a numeric badge to a resource navigation item with the number of records
 * that still require the administrator's attention: new leads, unconfirmed
 * bookings, orders awaiting processing or reviews pending moderation.
 *
 * The count is derived from the record workflow status, so it grows
 * automatically as soon as a new record arrives and shrinks (or disappears)
 * once the record is processed. A resource only describes which statuses count
 * as "unread" through {@see getUnreadBadgeQuery()}.
 */
trait HasUnreadBadge
{
    /**
     * Query of the records that should be counted as unread.
     */
    abstract public static function getUnreadBadgeQuery(): Builder;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getUnreadCount();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * @return string | array<string> | null
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): string|Htmlable|null
    {
        return 'Есть новые записи, требующие внимания';
    }

    public static function getUnreadCount(): int
    {
        return static::getUnreadBadgeQuery()->count();
    }
}
