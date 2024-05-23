<?php

declare(strict_types=1);

namespace App\ValueObject;

readonly class Money
{
    public function __construct(
        private string $money
    )
    {
    }

    public function toCents(): int
    {
        $cents = (float)number_format((float)str_replace([',', ' '], '', $this->money), 2, '.', '') * 100;

        return (int)str_replace('.', '', (string)$cents);
    }
}