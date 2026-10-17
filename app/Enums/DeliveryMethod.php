<?php

namespace App\Enums;

enum DeliveryMethod: string
{
    case Pickup = 'pickup';
    case CityDelivery = 'city_delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Самовывоз из студии',
            self::CityDelivery => 'Доставка по Брянску',
        };
    }
}
