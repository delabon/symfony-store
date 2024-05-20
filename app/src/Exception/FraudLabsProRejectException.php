<?php

namespace App\Exception;

use Exception;

class FraudLabsProRejectException extends Exception
{
    public function __construct($message = "Fraud detected", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
