<?php
/**
 * Element form.
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ElementType
 * @package Form
 */
class ElementType extends AbstractType
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
            ]
        );
        $builder->add(
            'value',
            NumberType::class,
            [
                'label' => 'label.value',
                'required' => false,
                'scale' => 2,
            ]
        );
        $builder->add(
            'quantity',
            NumberType::class,
            [
                'label' => 'label.quantity',
                'required' => false,
                'scale' => 1,
            ]
        );
        $builder->add(
            'isBought',
            ChoiceType::class,
            [
                'label' => 'label.isBought',
                'required' => true,
                'choices' => [
                    'label.yes' => 1,
                    'label.no' => 0,
                ],
            ]
        );
        $builder->add(
            'isItem',
            ChoiceType::class,
            [
                'label' => 'label.isItem',
                'required' => true,
                'choices' => [
                    'label.item' => 1,
                    'label.weight' => 0,
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'element_type';
    }
}
