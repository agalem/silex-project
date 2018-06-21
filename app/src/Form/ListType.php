<?php

/**
 * List form.
 */

namespace Form;

use PHP_CodeSniffer\Generators\Text;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ListType
 * @package Form
 */
class ListType extends AbstractType {

	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
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

	}


	/**
	 * @return string
	 */
	public function getBlockPrefix() {
		return 'list_type';
	}

}