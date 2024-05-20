<?php

namespace App\Service;

use App\Abstract\AbstractCheckoutDTO;
use App\Exception\InvalidCheckoutInputException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class CheckoutService
{
    public function __construct(
        private ValidatorInterface $validator
    )
    {
    }

    public function validateForm(AbstractCheckoutDTO $checkoutDTO): void
    {
        $errors = $this->validator->validate($checkoutDTO);

        if (count($errors)) {
            $returnErrors = [];

            foreach ($errors as $error) {
                /** @var ConstraintViolation $error */
                $returnErrors[$error->getPropertyPath()] = $error->getMessage();
            }

            throw new InvalidCheckoutInputException(errors: $returnErrors);
        }
    }

}