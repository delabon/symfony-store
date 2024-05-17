<?php

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
        return (int)(number_format(str_replace([',', ' '], '', $this->money), 2, '.', '') * 100);
    }
}