<?php

namespace Form;

use PHP_CodeSniffer\Generators\Text;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ListType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add(
			'name',
			TextType::class,
			[
				'label' => 'label.name',
				'required' => true,
				'attr' => [
					'max_length' => 128,
				],
			]
		);
		$builder->add(
			'maxCost',
			NumberType::class,
			[
				'label' => 'label.max_cost',
				'required' => false,
				'scale' => 1,
			]
		);
		$builder->get('elements')->addModelTransformer(
			new ElementsDataTransformer($options['elements_repository'])
		);
	}

	public function getBlockPrefix() {
		return 'list_type';
	}

}