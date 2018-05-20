<?php

namespace Form;

use Repository\ElementsRepository;
use Symfony\Component\Form\DataTransformerInterface;

class ElementsDataTransformer implements DataTransformerInterface {

	protected $elementsRepository = null;

	public function __construct(ElementsRepository $elementsRepository) {
		$this->elementsRepository = $elementsRepository;
	}

	public function transform( $elements ) {
		if (null == $elements) {
			return '';
		}

		return implode(',', $elements);
	}

	public function reverseTransform($string)
	{
		return explode(',', $string);
	}

}