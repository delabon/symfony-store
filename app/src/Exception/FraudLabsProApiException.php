<?php

namespace App\Exception;

use Exception;

class FraudLabsProApiException extends Exception
{
    public function __construct($message = "API connection failed", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
