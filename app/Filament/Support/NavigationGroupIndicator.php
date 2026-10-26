<?php

namespace App\Filament\Support;

/**
 * Builds the sidebar marker for a navigation group.
 *
 * Filament navigation groups have no native badge, so the marker is rendered
 * with CSS on the group header whenever at least one resource inside the group
 * reports unread records. The count is exposed through data attributes so the
 * marker can be styled or tested without touching Filament internals.
 */
final class NavigationGroupIndicator
{
    /**
     * Extra sidebar attributes for a navigation group.
     *
     * @param  array<class-string>  $resources
     * @return array<string, string>
     */
    public static function attributes(array $resources): array
    {
        $count = self::count($resources);

        if ($count === 0) {
            return [];
        }

        return [
            'class' => 'fi-nav-group-has-new',
            'data-nav-new-count' => (string) $count,
        ];
    }

    /**
     * Total number of unread records across the given resources.
     *
     * @param  array<class-string>  $resources
     */
    public static function count(array $resources): int
    {
        $count = 0;

        foreach ($resources as $resource) {
            $count += (int) $resource::getNavigationBadge();
        }

        return $count;
    }
}
