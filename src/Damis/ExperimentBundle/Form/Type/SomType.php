<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class SomType extends AbstractType
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
                        'notInRangeMessage' => 'Rows and columns quantity must be between {{ min }} and {{ max }}.',
                    ]),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer']),
                    new NotBlank()
                ],
                'label' => 'Number of rows',
                'label_attr' => ['class' => 'col-md-9']
            ])
            ->add('columns', IntegerType::class, [
                'required' => true,
                'data' => 10,
                'attr' => ['class' => 'form-control', 'min' => 3, 'max' => 100],
                'constraints' => [
                    new NotBlank(),
                    new Assert\Range([
                        'min' => 3,
                        'max' => 100,
                        'notInRangeMessage' => 'Rows and columns quantity must be between {{ min }} and {{ max }}.',
                    ]),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Number of columns',
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
                        'notInRangeMessage' => 'Number of training epochs must be between {{ min }} and {{ max }}.',
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Number of training epochs',
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
        return 'som_type';
    }
}