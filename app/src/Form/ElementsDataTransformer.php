<?php

namespace Form;

use Repository\ElementsRepository;
use Symfony\Component\Form\DataTransformerInterface;

class ElementsDataTransformer implements DataTransformerInterface {

	protected $elementsRepository = null;

	public function __construct(ElementsRepository $elementsRepository) {
		$this->elementsRepository = $elementsRepository;
	}

	public function transform($elements)
	{
		if (null == $elements) {
			return '';
		}

		$elementNames = [];

		foreach ($elements as $element) {
			$elementNames[] = $element['name'];
		}

		return implode(',', $elementNames);
	}

	public function reverseTransform($string)
	{
		$elementNames = explode(',', $string);

		$elements = [];

		foreach ($elementNames as $elementName) {
			if (trim($elementName) !== '') {
				$element = $this->elementsRepository->findOneByName($elementName);
				if (null === $element || !count($element)) {
					$element = [];
					$element['name'] = $elementName;
					$element = $this->elementsRepository->save($element);
				}
				$elements[] = $element;
			}
		}

		return $elements;
	}
}