<?php

namespace App\DTO;

use App\Abstract\AbstractCheckoutDTO;
use App\Validator\CardExpirationDate;
use Symfony\Component\Validator\Constraints AS Assert;

readonly class PaidCheckoutCheckoutDTO extends AbstractCheckoutDTO
{
    public function __construct(
        #[Assert\Valid]
        public CheckoutDetails $checkoutDetails,

        #[Assert\NotBlank]
        #[Assert\CardScheme(schemes: ['MASTERCARD', 'VISA', 'DISCOVER', 'JCB', 'AMEX', 'CHINA_UNIONPAY', 'MAESTRO'])]
        public string $ccNumber = '',

        #[Assert\NotBlank]
        #[CardExpirationDate]
        public string $ccDate = '',

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[0-9]{3,4}$/', message: 'Invalid CVC  code')]
        public string $ccCvc = '',
    ) {
    }

    public function getCcNumber(): string
    {
        return $this->ccNumber;
    }

    public function getCcDate(): string
    {
        return $this->ccDate;
    }

    public function getCcCvc(): string
    {
        return $this->ccCvc;
    }
}