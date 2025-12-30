<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class FilterType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('retFilteredData', ChoiceType::class, [
                'choices' => ['Without outliers' => 0, 'Only outliers' => 1],
                'required' => false,
                'data' => 0,
                'mapped' => false,
                'placeholder' => false,
                'multiple' => false,
                'expanded' => true,
                'constraints' => [
                    new NotBlank()
                ],
                'label' => 'Choose filtering result'
            ])
            ->add('zValue', NumberType::class, [
                'scale' => 2,
                'required' => true,
                'data' => 3.00,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0
                    ]),
                    new NotBlank()
                ],
                'label' => 'Z value'
            ])
            ->add('attrIndex', ChoiceType::class, [
                'choices' => $options['choices'],
                'required' => true,
                'label' => 'Attribute'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['choices', 'class']);

        $resolver->setDefaults([
            'translation_domain' => 'ExperimentBundle',
            'choices' => [],
            'class' => null,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'filter_type';
    }
}
