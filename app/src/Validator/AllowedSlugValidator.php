<?php

namespace App\Validator;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AllowedSlugValidator extends ConstraintValidator
{
    public function __construct(
        #[Autowire('%disallowed_slugs%')]
        private readonly array $disallowedSlugs
    )
    {
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var AllowedSlug $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (in_array($value, $this->disallowedSlugs, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
