<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RelativeMdsType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('d', IntegerType::class, [
                'required' => true,
                'data' => 2,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Value cannot be negative'
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'Projection space cannot be real value'])
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
                        'notInRangeMessage' => 'Number of iterations of MDS must be in interval [1; 1000]'
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Maximum number of iteration',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('noOfBaseVectors', IntegerType::class, [
                'required' => true,
                'data' => 1,
                'attr' => ['class' => 'form-control', 'min' => 0, 'max' => 100],
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 100,
                        'notInRangeMessage' => 'Relative basis object quantity must be in interval (0; 100] %'
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Relative number of basis objects',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('selStrategy', ChoiceType::class, [
                'placeholder' => false,
                'data' => 1,
                'choices' => ['Random' => 1, 'By line based on PCA' => 2, 'By line based on max variable' => 3],
                'attr' => ['class' => 'form-control'],
                'label' => 'Select Basis objects strategy',
                'label_attr' => ['class' => 'col-md-7'],

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
        return 'relativemds_type';
    }
}