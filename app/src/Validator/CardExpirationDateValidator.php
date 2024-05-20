<?php

namespace App\Validator;

use DateTime;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CardExpirationDateValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var CardExpirationDate $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        $ccDate = DateTimeImmutable::createFromFormat('m/y', $value);

        if (!$ccDate) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();

            return;
        }

        $now = new DateTimeImmutable();

        if ($now->format('y') > $ccDate->format('y') || ($now->format('y') === $ccDate->format('y') && $now->format('m') > $ccDate->format('m'))) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
