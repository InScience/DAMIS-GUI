<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class SplitDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('reshufleObjects', ChoiceType::class, [
                'required' => false,
                'placeholder' => false,
                'data' => 0,
                'expanded' => true,
                'choices' => [
                    'Order left intact' => 0,
                    'Random' => 1
                ],
                'constraints' => [
                    new NotBlank()
                ],
                'label' => 'Choose object sort type',
                'label_attr' => ['class' => 'col-md-9']
            ])
            ->add('firstSubsetPerc', IntegerType::class, [
                'required' => true,
                'data' => 80,
                'attr' => ['class' => 'form-control', 'min' => 0, 'max' => 100],
                'constraints' => [
                    new NotBlank(),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'Number of percents must be between {{ min }} and {{ max }}.',
                    ]),
                    new Assert\Type([
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ])
                ],
                'label' => 'First subset size',
                'label_attr' => ['class' => 'col-md-8']
            ])
            ->add('secondSubsetPerc', IntegerType::class, [
                'required' => true,
                'data' => 20,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'disabled' => true,
                'constraints' => [
                    new NotBlank(),
                    new Assert\Type([
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ])
                ],
                'label' => 'Second subset size',
                'label_attr' => ['class' => 'col-md-8']
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

    public function getBlockPrefix(): string
    {
        return 'splitdata_type';
    }
}