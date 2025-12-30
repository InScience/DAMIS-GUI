<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SomMdsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rows', IntegerType::class, [
                'required' => true,
                'data' => 10,
                'attr' => ['class' => 'form-control', 'min' => 3, 'max' => 100],
                'constraints' => [
                    new Assert\Range([
                        'min' => 3,
                        'max' => 100,
                        'notInRangeMessage' => 'SOM rows and columns quantity must be between {{ min }} and {{ max }}.',
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Number of rows of SOM',
                'label_attr' => ['class' => 'col-md-9']
            ])
            ->add('columns', IntegerType::class, [
                'required' => true,
                'data' => 10,
                'attr' => ['class' => 'form-control', 'min' => 3, 'max' => 100],
                'constraints' => [
                    new Assert\Range([
                        'min' => 3,
                        'max' => 100,
                        'notInRangeMessage' => 'SOM rows and columns quantity must be between {{ min }} and {{ max }}.',
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Number of columns of SOM',
                'label_attr' => ['class' => 'col-md-9']
            ])
            ->add('eHat', IntegerType::class, [
                'required' => true,
                'data' => 100,
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 1000],
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 1000,
                        'notInRangeMessage' => 'Number of SOM training epochs must be between {{ min }} and {{ max }}.',
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Number of SOM training epochs',
                'label_attr' => ['class' => 'col-md-9']
            ])
            ->add('mdsProjection', IntegerType::class, [
                'required' => true,
                'data' => 2,
                'attr' => ['class' => 'form-control', 'min' => 2, 'max' => 2],
                'constraints' => [
                    new Assert\Range([
                        'min' => 2,
                        'max' => 2,
                        'notInRangeMessage' => 'MDS projection must be exactly {{ min }}.',
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'disabled' => true,
                'label' => 'Projection space of MDS',
                'label_attr' => ['class' => 'col-md-9']
            ])
            ->add('mdsIteration', IntegerType::class, [
                'required' => true,
                'data' => 100,
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 1000],
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 1000,
                        'notInRangeMessage' => 'Number of iterations of MDS must be between {{ min }} and {{ max }}.',
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Number of iterations of MDS',
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

    public function getBlockPrefix(): string
    {
        return 'sommds_type';
    }
}