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
        ->add('rowsNumber', 'integer', [
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
                    new NotBlank()
                ],
                'label' => 'Number of rows of SOM',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('colsNumber', 'integer', [
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
                    new NotBlank()
                ],
                'label' => 'Number of columns of SOM',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('epochsNumber', 'integer', [
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
                    new NotBlank()
                ],
                'label' => 'Number of SOM training epochs',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('projectionSpace', 'integer', [
                'required' => true,
                'data' => 2,
                'attr' => array('class' => 'form-control'),
                'read_only' => true,
                'label' => 'Projection space of MDS',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('iterations', 'integer', [
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
                    new NotBlank()
                ],
                'label' => 'Number of iterations of MDS',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('calculationError', 'text', [
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'data' => '0.0001',
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0.00000001,
                        'message' => 'Number of calculation errors difference must be in interval [10^-8; ∞)'
                    ]),
                    new NotBlank()
                ],
                'label' => 'Difference between calculation errors',
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
