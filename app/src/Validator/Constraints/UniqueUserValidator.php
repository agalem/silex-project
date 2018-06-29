<?php

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueUserValidator
 */
class UniqueUserValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint->repository) {
            return;
        }

        $result = $constraint->repository->findForUniqueness(
            $value
        );

        if ($result && count($result)) {
            $this->context->buildViolation($constraint->message)
                          ->setParameter('{{ user }}', $value)
                          ->addViolation();
        }
    }
}
