<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

    class MlpType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
        ->add('maxIterations', 'number', [
            'required' => true,
            'data' => 100,
            'constraints' => [
                new Assert\Range([
                    'min' => 0,
                    'max' => 1000
                ]),
                new NotBlank()
            ]
            ])
        ->add('1layer', 'number', [
                'required' => true,
                'data' => 5,
            ])
        ->add('2layer', 'number', [
                'required' => true,
                'data' => 0,
            ])
        ->add('3layer', 'number', [
            'required' => true,
            'data' => 0,
            ])
        ->add('trainingData', 'number', [
                'required' => true,
                'data' => 80,
            ])
        ->add('testData', 'number', [
                'required' => true,
                'data' => 10,
            ])
        ->add('valiadationData', 'number', [
            'required' => true,
            'data' => 10,
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'mlp_type';
    }

}
