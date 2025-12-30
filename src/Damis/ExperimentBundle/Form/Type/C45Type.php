<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * This class is currently unused
 */
class C45Type extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('q', NumberType::class, [
                'required' => true,
                'data' => 0.25,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 1,
                        'notInRangeMessage' => 'Confidence level must be in interval (0; 1]',
                    ]),
                ],
                'label' => 'Confidence level',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('dL', IntegerType::class, [
                'required' => true,
                'data' => 80,
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 100],
                'constraints' => [
                    new Assert\Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'Number of percents in interval [0; 100]',
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Size of training data',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('dT', IntegerType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 100],
                'disabled' => true,
                'data' => 20,
                'constraints' => [
                    new Assert\Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'Number of percents in interval [0; 100]',
                    ]),
                    new NotBlank()
                ],
                'label' => 'Size of test data',
                'label_attr' => ['class' => 'col-md-8']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['translation_domain' => 'ExperimentBundle']);
    }

    public function getBlockPrefix()
    {
        return 'c45_type';
    }
}