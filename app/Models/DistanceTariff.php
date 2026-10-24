<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Тариф удалённости: интервал километража от офиса и наценка на участника.
 * Пустая верхняя граница означает «от min_distance и далее».
 */
#[Fillable(['min_distance', 'max_distance', 'surcharge_per_participant'])]
class DistanceTariff extends Model
{
    protected function casts(): array
    {
        return [
            'min_distance' => 'integer',
            'max_distance' => 'integer',
            'surcharge_per_participant' => 'integer',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('min_distance')->orderBy('max_distance');
    }

    public function covers(float $distanceKm): bool
    {
        if ($distanceKm < $this->min_distance) {
            return false;
        }

        return $this->max_distance === null || $distanceKm <= $this->max_distance;
    }

    /**
     * Подбирает тариф для расстояния. Дистанции ближе первого интервала
     * (сам город) остаются без наценки, а дистанции дальше последнего
     * интервала тарифицируются по самому дальнему тарифу, чтобы дальняя
     * поездка не оказалась дешевле ближней.
     */
    public static function forDistance(?float $distanceKm): ?self
    {
        if ($distanceKm === null || $distanceKm < 0) {
            return null;
        }

        $tariffs = self::query()->ordered()->get();

        $covered = $tariffs->first(fn (self $tariff) => $tariff->covers($distanceKm));

        if ($covered !== null) {
            return $covered;
        }

        return $tariffs->last(fn (self $tariff) => $distanceKm >= $tariff->min_distance);
    }

    public function surchargeCents(): int
    {
        return max(0, $this->surcharge_per_participant) * 100;
    }

    public function intervalLabel(): string
    {
        if ($this->max_distance === null) {
            return 'от '.$this->min_distance.' км';
        }

        return $this->min_distance.'–'.$this->max_distance.' км';
    }

    /**
     * Сетка тарифов для клиентского калькулятора: страница отдаёт её сразу,
     * чтобы JS пересчитывал стоимость без дополнительных запросов.
     *
     * @return array<int, array{min:int,max:int|null,surcharge:int}>
     */
    public static function frontendRows(): array
    {
        return self::query()
            ->ordered()
            ->get()
            ->map(fn (self $tariff) => [
                'min' => $tariff->min_distance,
                'max' => $tariff->max_distance,
                'surcharge' => $tariff->surcharge_per_participant,
            ])
            ->values()
            ->all();
    }
}
