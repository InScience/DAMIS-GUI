<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class DmaType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
        ->add('neighbour', 'number', [
                'required' => true,
                'data' => 1,
                'attr' => array('class' => 'form-control'),
                'constraints' => [
                    new NotBlank(),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 100,
                        'minMessage' => 'Relative neighbor quantity must be in interval (0; 100] %',
                        'maxMessage' => 'Relative neighbor quantity must be in interval (0; 100] %'
                    ]),
                ],
                'label' => 'Relative number of neighbours',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('d', 'integer', [
                'required' => true,
                'data' => 2,
                'attr' => array('class' => 'form-control'),
                'constraints' => [
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Projection space',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('maxIteration', 'integer', [
            'required' => true,
            'data' => 100,
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
            'attr' => ['class' => 'form-control'],
            'label_attr' => ['class' => 'col-md-9']
        ])
        ->add('eps', 'text', [
            'required' => true,
            'attr' => array('class' => 'form-control'),
            'data' => '0.0001',
            'constraints' => [
                new Assert\GreaterThanOrEqual([
                    'value' => 0.0001,
                    'message' => 'Minimal stress change must be in interval [10^-8; ∞)'
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
        return 'dma_type';
    }

}
