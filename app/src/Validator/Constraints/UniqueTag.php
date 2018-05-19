<?php
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueTag extends Constraint {

	public $message = '{{ tag }} is not unique Tag';

	public $elementId = null;

	public $repository = null;

}