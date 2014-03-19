<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

    class MlpType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $dataValidator = function($object, ExecutionContextInterface $context) use ($builder) {
            $data = $builder->getData();
            $testData = $data['testData'];
            $validationData = $data['validationData'];
            if($object + $testData + $validationData != 100) {
                $context->addViolation('Sum of data fields should be 100%', [], null);
            }
        };

        $builder
        ->add('maxIterations', 'number', [
                'required' => true,
                'data' => 100,
                'constraints' => [
                    new Assert\Range([
                        'min' => 0,
                        'max' => 1000,
                        'invalidMessage' => 'Number of iteration must be in interval [1; 1000]'
                    ]),
                    new NotBlank()
                ]
            ])
        ->add('1layer', 'number', [
                'required' => true,
                'precision' => 0,
                'data' => 5,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Number of hidden neurons at level 1 must be greater than 1'
                    ]),
                    new NotBlank()
                ]

            ])
        ->add('2layer', 'number', [
                'required' => true,
                'data' => 0,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Number of hidden neurons at level 2, 3 cannot be negative'
                    ]),
                    new NotBlank()
                ]
            ])
        ->add('3layer', 'number', [
                'required' => true,
                'data' => 0,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Number of hidden neurons at level 2, 3 cannot be negative'
                    ]),
                    new NotBlank()
                ]

            ])
        ->add('trainingData', 'number', [
                'required' => true,
                'data' => 80,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                    ]),
                    new NotBlank(),
                    $dataValidator
                ]

            ])
        ->add('testData', 'number', [
                'required' => true,
                'data' => 10,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                    ]),
                    new NotBlank()
                ]
            ])
        ->add('valiadationData', 'number', [
                'required' => true,
                'data' => 10,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                    ]),
                    new NotBlank()
                ]
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
