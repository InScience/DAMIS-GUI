<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class SomMdsType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
        ->add('rows', 'integer', [
                'required' => true,
                'data' => 10,
                'attr' => array('class' => 'form-control'),
                'constraints' => [
                    new Assert\Range([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'SOM rows and columns quantity must be in interval [3; 100]',
                        'maxMessage' => 'SOM rows and columns quantity must be in interval [3; 100]'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Number of rows of SOM',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('columns', 'integer', [
                'required' => true,
                'data' => 10,
                'attr' => array('class' => 'form-control'),
                'constraints' => [
                    new Assert\Range([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'SOM rows and columns quantity must be in interval [3; 100]',
                        'maxMessage' => 'SOM rows and columns quantity must be in interval [3; 100]'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Number of columns of SOM',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('eHat', 'integer', [
                'required' => true,
                'data' => 100,
                'attr' => array('class' => 'form-control'),
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 1000,
                        'minMessage' => 'Number of SOM training epochs must be in interval [1; 1000]',
                        'maxMessage' => 'Number of SOM training epochs must be in interval [1; 1000]'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Number of SOM training epochs',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('mdsProjection', 'integer', [
                'required' => true,
                'data' => 2,
                'attr' => array('class' => 'form-control'),
                'read_only' => true,
                'label' => 'Projection space of MDS',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('mdsIteration', 'integer', [
                'required' => true,
                'data' => 100,
                'attr' => array('class' => 'form-control'),
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 1000,
                        'minMessage' => 'Number of iterations of MDS must be in interval [1; 1000]',
                        'maxMessage' => 'Number of iterations of MDS must be in interval [1; 1000]'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Number of iterations of MDS',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('eps', 'text', [
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'data' => '0.0001',
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0.00000001,
                        'message' => 'Minimal stress change must be in interval [10-8; ∞)'
                    ]),
                    new NotBlank()
                ],
                'label' => 'Minimal stress change',
                'label_attr' => ['class' => 'col-md-9']
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'sommds_type';
    }

}
