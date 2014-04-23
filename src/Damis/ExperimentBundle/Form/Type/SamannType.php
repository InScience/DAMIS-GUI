<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class SamannType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('d', 'integer', [
                'required' => true,
                'data' => 2,
                'read_only' => true,
                'attr' => array('class' => 'form-control'),
                'constraints' => [
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Projection space',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('maxIteration', 'integer', [
                'required' => true,
                'data' => 100,
                'attr' => array('class' => 'form-control'),
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
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('mTrain', 'number', [
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'data' => 10.0,
                'constraints' => [
                    new Assert\Range([
                        'min' => 0.00000000001,
                        'max' => 100,
                        'minMessage' => 'Relative size of the training data must be in interval (0; 100] %',
                        'maxMessage' => 'Relative size of the training data must be in interval (0; 100] %'
                    ]),
                    new NotBlank()
                ],
                'label' => 'Relative size of the training data',
                'label_attr' => ['class' => 'col-md-8']
            ])
            ->add('nNeurons', 'number', [
                'required' => true,
                'data' => 10,
                'attr' => array('class' => 'form-control'),
                'constraints' => [
                    new NotBlank(),
                    new Assert\GreaterThan([
                        'value' => 0,
                        'message' => 'Number of neurons in the hidden layer must be greater than 0'
                    ]),
                ],
                'label' => 'Number of neurons in the hidden layer',
                'label_attr' => ['class' => 'col-md-8']
            ])
            ->add('eta', 'number', [
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'data' => 1.0,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Value must be greater than 0'
                    ]),
                    new NotBlank()
                ],
                'label' => 'Value of the learning rate',
                'label_attr' => ['class' => 'col-md-8']
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'samann_type';
    }

}
