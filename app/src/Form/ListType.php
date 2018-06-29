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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ListType
 *
 */
class ListType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.name',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['list-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['list-default'],
                            'min' => 3,
                            'max' => 125,
                        ]
                    ),

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
    public function getBlockPrefix()
    {
        return 'list_type';
    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'list-default',
            ]
        );
    }
}
