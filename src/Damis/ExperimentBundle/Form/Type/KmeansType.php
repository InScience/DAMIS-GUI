<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class KmeansType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
        ->add('kMax', IntegerType::class, [
            'required' => true,
            'data' => 10,
            'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 100],
            'constraints' => [
                new Assert\Range([
                    'min' => 1,
                    'max' => 100,
                    'notInRangeMessage' => 'Number of cluster must be in interval [1; 100]'
                ]),
                new NotBlank(),
                new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
            ],
            'label' => 'Maximum number of cluster',
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
        return 'kmeans_type';
    }
}