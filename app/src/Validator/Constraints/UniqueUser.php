<?php

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueUser
 * @package Validator\Constraints
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