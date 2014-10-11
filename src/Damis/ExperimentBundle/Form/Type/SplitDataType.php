<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class SplitDataType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
        ->add('reshufleObjects', 'choice', [
                'required' => false,
                'empty_value' => false,
                'data' => 0,
                'expanded' => true,
                'choices' => array(
                    0 => 'Order left intact',
                    1 => 'Random'
                ),
                'constraints' => [
                    new NotBlank()
                ],
                'label' => 'Choose object sort type',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('firstSubsetPerc', 'integer', [
                'required' => true,
                'data' => 80,
                'attr' => array('class' => 'form-control', 'min' => 0, 'max' => 100),  
                'constraints' => [
                    new NotBlank(),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 100,
                        'minMessage' => 'Number of percents must be in interval [0; 100]',
                        'maxMessage' => 'Number of percents must be in interval [0; 100]'
                    ]),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'First subset size',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('secondSubsetPerc', 'integer', [
                'required' => true,
                'data' => 20,
                'attr' => array('class' => 'form-control', 'min' => 1),
                'read_only' => true,
                'constraints' => [
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Second subset size',
                'label_attr' => ['class' => 'col-md-8']
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'splitdata_type';
    }

}
