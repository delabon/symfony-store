<?php

namespace App\Tests\ValueObject;

use App\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @dataProvider moneyProvider
     * @return void
     */
    public function testMoneyReturnsCorrectCents($amount, $expectedAmount): void
    {
        $this->assertTrue(true);

        $money = new Money($amount);
        $this->assertSame($expectedAmount, $money->toCents());
    }

    public function moneyProvider(): array
    {
        return [
            ['0.5', 50],
            ['0.64', 64],
            ['1', 100],
            ['1.34', 134],
            ['100', 10000],
            ['12,765.65', 1276565],
            ['12,765.6', 1276560],
            ['12,765.693', 1276569],
            ['12,765.641', 1276564],
            ['3 712,765.641', 371276564],
        ];
    }
}
