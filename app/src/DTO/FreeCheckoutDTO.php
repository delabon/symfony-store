<?php

namespace App\DTO;

use App\Abstract\AbstractCheckoutDTO;
use Symfony\Component\Validator\Constraints AS Assert;

readonly class FreeCheckoutDTO extends AbstractCheckoutDTO
{
    public function __construct(
        #[Assert\Valid]
        public CheckoutDetails $checkoutDetails
    ) {
    }
}