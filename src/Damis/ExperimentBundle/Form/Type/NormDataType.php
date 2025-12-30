<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class NormDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('normMeanStd', ChoiceType::class, [
                'placeholder' => false,
                'required' => false,
                'data' => 1,
                'expanded' => true,
                'choices' => [
                    'Mean a, Standard deviation b' => 1,
                    'Interval [a;b]' => 0
                ],
                'constraints' => [
                    new NotBlank()
                ],
                'label' => 'Choose norm method',
                'label_attr' => ['class' => 'col-md-9']
            ])
            ->add('a', NumberType::class, [
                'required' => true,
                'data' => 0,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => 'a',
                'label_attr' => ['class' => 'col-md-1']
            ])
            ->add('b', NumberType::class, [
                'required' => true,
                'data' => 1,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(),
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Value must be greater than 0'
                    ]),
                ],
                'label' => 'b',
                'label_attr' => ['class' => 'col-md-1']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $formValidator = function ($data, ExecutionContextInterface $context) {
            $method = $data['normMeanStd'] ?? null;
            $a = $data['a'] ?? null;
            $b = $data['b'] ?? null;

            if ($method !== null && $method == 0) { 
                if ($a !== null && $b !== null && $a >= $b) {
                    $context->buildViolation('Interval upper bound must be greater than lower')
                        ->atPath('a')
                        ->addViolation();
                }
            }
        };

        $resolver->setDefined(['choices', 'class']);

        $resolver->setDefaults([
            'translation_domain' => 'ExperimentBundle',
            'choices' => [],
            'class' => null,
            'constraints' => [
                new Callback($formValidator)
            ]
        ]);
    }


    public function getBlockPrefix()
    {
        return 'normdata_type';
    }
}