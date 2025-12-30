<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class MlpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Validate parameter of qty
        // Removed 'use ($builder)' as it was not used inside the function
        $dataValidator = function ($object, ExecutionContextInterface $context) {
            // WARNING: accessing $_POST directly in a FormType is not recommended in Symfony,
            // but kept here to maintain your existing logic.
            $mlp = $_POST['mlp_type'] ?? null; 

            if (!$mlp) {
                return;
            }

            // Training and testing sets
            if (isset($mlp['kFoldValidation']) && $mlp['kFoldValidation'] == '0') {
                if ($mlp['qty'] < 1 || $mlp['qty'] > 100) {
                    $context->addViolation('Training data size k must be in interval [1; 100] %', [], null);
                }
            // K fold training
            } elseif (isset($mlp['kFoldValidation']) && $mlp['kFoldValidation'] == '1') {
                //@TODO This parameter should be bounded by data elements number
                if ($mlp['qty'] < 2 || $mlp['qty'] > 150) {
                    $context->addViolation('Cross validation fold number k must be in interval [2; 150]', [], null);
                }
            }
        };

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
                'label_attr' => ['class' => 'col-md-7']
            ])
        ->add('h1pNo', IntegerType::class, [
                'required' => true,
                'data' => 5,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Number of hidden neurons at level 1 must be greater than 1'
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'First layer',
            ])
        ->add('h2pNo', IntegerType::class, [
                'required' => true,
                'data' => 0,
                'attr' => ['class' => 'form-control', 'min' => 0],
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Number of hidden neurons at level 2 cannot be negative'
                    ]),
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer'])
                ],
                'label' => 'Second layer',
            ])
        ->add('kFoldValidation', ChoiceType::class, [
                'required' => false,
                'placeholder' => false,
                'data' => 0,
                'expanded' => true,
                'choices' => ['Size of training sets (%)' => 0, 'Number of cross validation folds' => 1],
                'constraints' => [
                    new NotBlank()
                ],
                'label' => 'Select training parameter k',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('qty', NumberType::class, [
                'required' => true,
                'data' => 90,
                'constraints' => [
                    new NotBlank(),
                    new Callback($dataValidator) 
                ],
                'label' =>'Parameter k',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'col-md-7']
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
        return 'mlp_type';
    }
}