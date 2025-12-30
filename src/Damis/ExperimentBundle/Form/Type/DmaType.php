<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DmaType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
        ->add('neighbour', IntegerType::class, [
                'required' => true,
                'data' => 1,
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 100],
                'constraints' => [
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 100,
                        'notInRangeMessage' => 'Relative neighbor quantity must be in interval (0; 100] %'
                    ]),
                ],
                'label' => 'Relative number of neighbours',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('d', IntegerType::class, [
                'required' => true,
                'data' => 2,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'invalid_message' => 'This value type should be integer',
                'constraints' => [
                    new NotBlank(),
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'This value type should be integer'
                    ]),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Projection space',
                'label_attr' => ['class' => 'col-md-9']
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
            'label_attr' => ['class' => 'col-md-9']
        ])
        ->add('eps', TextType::class, [
            'required' => true,
            'attr' => ['class' => 'form-control'],
            'data' => '0.0001',
            'constraints' => [
                new Assert\GreaterThanOrEqual([
                    'value' => 0.00000001,
                    'message' => 'Minimal stress change must be in interval [10^-8; ∞)'
                ]),
                new NotBlank()
            ],
            'label' => 'Minimal stress change',
            'label_attr' => ['class' => 'col-md-9']
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
        return 'dma_type';
    }
}