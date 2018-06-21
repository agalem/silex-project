<?php

/**
 * Change password form.
 */
namespace Form;

use PHP_CodeSniffer\Generators\Text;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ChangePasswordType
 * @package Form
 */
class ChangePasswordType extends AbstractType {

	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add(
			'password',
			TextType::class,
			[
				'label' => 'label.new_password',
				'required' => true,
				'attr' => [
					'max_length' => 45,
					'min_length' => 8,
				],
			]
		);

	}

	/**
	 * @return string
	 */
	public function getBlockPrefix() {
		return 'change_password_type';
	}

}