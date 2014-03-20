<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class MlpType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $dataValidator = function($object, ExecutionContextInterface $context) use ($builder) {
            $mlp = $_POST['mlp_type'];
            if($object + $mlp['testData'] + $mlp['validationData'] != 100) {
                $context->addViolation('Sum of data fields should be 100%', [], null);
            }
        };

        $builder
        ->add('maxIterations', 'integer', [
                'required' => true,
                'data' => 100,
                'constraints' => [
                    new Assert\Range([
                        'min' => 0,
                        'max' => 1000,
                        'minMessage' => 'Number of iteration must be in interval [1; 1000]',
                        'maxMessage' => 'Number of iteration must be in interval [1; 1000]'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Maximum number of iteration',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'col-md-7']
            ])
        ->add('firLayer', 'integer', [
                'required' => true,
                'precision' => 0,
                'data' => 5,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Number of hidden neurons at level 1 must be greater than 1'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'First layer',
                'attr' => ['class' => 'form-control']
            ])
        ->add('secLayer', 'integer', [
                'required' => true,
                'data' => 0,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Number of hidden neurons at level 2, 3 cannot be negative'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Second layer',
                'attr' => ['class' => 'form-control']
            ])
        ->add('thrLayer', 'integer', [
                'required' => true,
                'data' => 0,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Number of hidden neurons at level 2, 3 cannot be negative'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Third layer',
                'attr' => ['class' => 'form-control']
            ])
        ->add('trainingData', 'number', [
                'required' => true,
                'data' => 80,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                    ]),
                    new NotBlank(),
                    new Callback([$dataValidator])
                ],
                'label' =>'Size of training data',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'col-md-7']
            ])
        ->add('testData', 'number', [
                'required' => true,
                'data' => 10,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                    ]),
                    new NotBlank()
                ],
                'label' =>'Size of test data',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'col-md-7']
            ])
        ->add('validationData', 'number', [
                'required' => true,
                'data' => 10,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                    ]),
                    new NotBlank()
                ],
                'label' =>'Size of validation data',
                'attr' => ['class' => 'form-control', 'readonly' => true],
                'label_attr' => ['class' => 'col-md-7'],
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
