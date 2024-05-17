<?php

namespace App\Utility;

class StringToFloatUtility
{
    public static function convert(string $number): float
    {
        return floatval(preg_replace("/[^0-9\.\-]/", "", $number));
    }
}