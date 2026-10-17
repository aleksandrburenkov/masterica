<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_money_formats_rubles(): void
    {
        $this->assertSame('1 500 ₽', money(150000));
        $this->assertSame('2 700 ₽', money(270000));
        $this->assertSame('0 ₽', money(0));
        $this->assertSame('1 500,50 ₽', money(150050));
    }

    public function test_currency_symbol(): void
    {
        $this->assertSame('₽', currency_symbol('RUB'));
        $this->assertSame('$', currency_symbol('USD'));
        $this->assertSame('€', currency_symbol('EUR'));
    }
}
