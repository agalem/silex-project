<?php

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueUser
 */
class UniqueUser extends Constraint
{
    /**
     * @var string
     */
    public $message = '{{ user }} already exists.';

    /**
     * @var null
     */
    public $repository = null;
}
