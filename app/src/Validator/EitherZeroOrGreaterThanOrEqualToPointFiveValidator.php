<?php

namespace App\Validator;

use App\Utility\StringToFloatUtility;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EitherZeroOrGreaterThanOrEqualToPointFiveValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        /* @var EitherZeroOrGreaterThanOrEqualToPointFive $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        $value = StringToFloatUtility::convert($value);

        if ($value == 0 || $value >= 0.5) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
