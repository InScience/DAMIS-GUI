<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

class FilterType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('retFilteredData', 'choice', [
                'choices' => [0 => 'Without outliers', 1 => 'Only outliers'],
                'required' => false,
                'data' => 0,
                'mapped' => false,
                'empty_value' => false,
                'multiple' => false,
                'expanded' => true,
                'constraints' => [
                    new NotBlank()
                ],
                'label' => 'Choose filtering result'
            ])
            ->add('zValue', 'number', [
                'precision' => 2,
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
            ->add('attrIndex', 'choice', [
                'choices' => $options['data']['choices'],
                'required' => true,
                'label' => 'Attribute'
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'filter_type';
    }

}
