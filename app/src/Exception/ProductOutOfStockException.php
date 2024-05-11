<?php

namespace App\Exception;

use Exception;

class ProductOutOfStockException extends Exception
{
    public function __construct()
    {
        parent::__construct('Product out of stock');
    }
}