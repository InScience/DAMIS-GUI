<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class MlpType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        // Validate parameter of qty
        $dataValidator = function($object, ExecutionContextInterface $context) use ($builder) {
            $mlp = $_POST['mlp_type'];
            // Training and testing sets
            if ($mlp['kFoldValidation'] == '0') {
                if($mlp['qty'] < 1 || $mlp['qty'] > 100) {
                    $context->addViolation('Training data size k must be in interval [1; 100] %', [], null);
                }
            // K fold training
            } else if ($mlp['kFoldValidation'] == '1') {
                if($mlp['qty'] < 2 || $mlp['qty'] > 100) {
                    $context->addViolation('Cross validation fold number k must be in interval [2; 100]', [], null);
                }
            }
        };

        $builder
        ->add('maxIteration', 'integer', [
                'required' => true,
                'data' => 100,
                'attr' => array('class' => 'form-control', 'min' => 1, 'max' => 1000),
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 1000,
                        'minMessage' => 'Number of iteration must be in interval [1; 1000]',
                        'maxMessage' => 'Number of iteration must be in interval [1; 1000]'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Maximum number of iteration',
                'label_attr' => ['class' => 'col-md-7']
            ])
        ->add('h1pNo', 'integer', [
                'required' => true,
                'precision' => 0,
                'data' => 5,
                'attr' => array('class' => 'form-control', 'min' => 1),
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Number of hidden neurons at level 1 must be greater than 1'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'First layer',
            ])
        ->add('h2pNo', 'integer', [
                'required' => true,
                'data' => 0,
                'attr' => array('class' => 'form-control', 'min' => 0),
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Number of hidden neurons at level 2 cannot be negative'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Second layer',
            ])
        ->add('kFoldValidation', 'choice', [
                'required' => false,
                'empty_value' => false,
                'data' => 0,
                'expanded' => true,
                'choices' => array(
                    0 => 'Size of training sets (%)',
                    1 => 'Number of cross validation folds'
                ),
                'constraints' => [
                    new NotBlank()
                ],
                'label' => 'Select training parameter k',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('qty', 'number', [
                'required' => true,
                'data' => 90,
                'constraints' => [
                    new NotBlank(),
                    new Callback([$dataValidator])
                ],
                'label' =>'Parameter k',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'col-md-7']
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'mlp_type';
    }

}
