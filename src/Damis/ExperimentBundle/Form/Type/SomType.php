<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

class SomType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
        ->add('rows', 'integer', [
                'required' => true,
                'data' => 10,
                'attr' => array('class' => 'form-control', 'min' => 3, 'max' => 100),
                'constraints' => [
                    new Assert\Range([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Rows and columns quantity must be in interval [3; 100]',
                        'maxMessage' => 'Rows and columns quantity must be in interval [3; 100]'
                    ]),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    )),
                    new NotBlank()
                ],
                'label' => 'Number of rows',
                'label_attr' => ['class' => 'col-md-9']
            ])
            ->add('columns', 'integer', [
                'required' => true,
                'data' => 10,
                'attr' => array('class' => 'form-control', 'min' => 3, 'max' => 100),
                'constraints' => [
                    new NotBlank(),
                    new Assert\Range([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Rows and columns quantity must be in interval [3; 100]',
                        'maxMessage' => 'Rows and columns quantity must be in interval [3; 100]'
                    ]),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Number of columns',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('eHat', 'integer', [
                'required' => true,
                'data' => 100,
                'attr' => array('class' => 'form-control', 'min' => 1, 'max' => 1000),
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 1000,
                        'minMessage' => 'Number of training epochs mus be in interval [1; 1000]',
                        'maxMessage' => 'Number of training epochs mus be in interval [1; 1000]'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Number of training epochs',
                'label_attr' => ['class' => 'col-md-9']
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'som_type';
    }

}
