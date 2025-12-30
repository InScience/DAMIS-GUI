<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class SamannType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('d', IntegerType::class, [
                'required' => true,
                'data' => 2,
                'disabled' => true,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Projection space',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('maxIteration', IntegerType::class, [
                'required' => true,
                'data' => 100,
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 1000],
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 1000,
                        'notInRangeMessage' => 'Number of iteration must be in interval [1; 1000]'
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Maximum number of iteration',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('mTrain', NumberType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'data' => 10.0,
                'constraints' => [
                    new Assert\Range([
                        'min' => 0.00000000001,
                        'max' => 100,
                        'notInRangeMessage' => 'Relative size of the training data must be in interval (0; 100] %'
                    ]),
                    new NotBlank()
                ],
                'label' => 'Relative size of the training data',
                'label_attr' => ['class' => 'col-md-8']
            ])
            ->add('nNeurons', NumberType::class, [
                'required' => true,
                'data' => 10,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(),
                    new Assert\GreaterThan([
                        'value' => 0,
                        'message' => 'Number of neurons in the hidden layer must be greater than 0'
                    ]),
                    new Assert\Regex(['pattern' => '/^[0-9]+$/', 'match' =>  true, 'message' => 'This value type should be integer'])
                ],
                'label' => 'Number of neurons in the hidden layer',
                'label_attr' => ['class' => 'col-md-8']
            ])
            ->add('eta', NumberType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'data' => 1.0,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Value must be greater than 0'
                    ]),
                    new NotBlank()
                ],
                'label' => 'Value of the learning rate',
                'label_attr' => ['class' => 'col-md-8']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['choices', 'class']);
        
        $resolver->setDefaults([
            'translation_domain' => 'ExperimentBundle',
            'choices' => [],
            'class' => [],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'samann_type';
    }
}