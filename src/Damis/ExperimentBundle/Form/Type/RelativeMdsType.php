<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

class RelativeMdsType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
        ->add('d', 'integer', [
                'required' => true,
                'data' => 2,
                'attr' => array('class' => 'form-control', 'min' => 1),
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Value cannot be negative'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'Projection space cannot be real value'
                    ))
                ],
                'label' => 'Projection space',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('maxIteration', 'integer', [
                'required' => true,
                'data' => 100,
                'attr' => array('class' => 'form-control', 'min' => 1, 'max' => 1000),
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
                'label' => 'Maximum number of iteration',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('noOfBaseVectors', 'integer', [
                'required' => true,
                'data' => 1,
                'attr' => array('class' => 'form-control', 'min' => 0, 'max' => 100),
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 100,
                        'minMessage' => 'Relative basis object quantity must be in interval (0; 100] %',
                        'maxMessage' => 'Relative basis object quantity must be in interval (0; 100] %'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Relative number of basis objects',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('selStrategy', 'choice', [
                'empty_value' => false,
                'data' => 1,
                'choices' => array(
                    1 => 'Random',
                    2 => 'By line based on PCA',
                    3 => 'By line based on max variable'
                ),
                'attr' => array('class' => 'form-control'),
                'label' => 'Select Basis objects strategy',
                'label_attr' => ['class' => 'col-md-7'],

            ])
        ->add('eps', 'text', [
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'data' => '0.0001',
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0.00000001,
                        'message' => 'Minimal stress change must be in interval [10^-8; ∞)'
                    ]),
                    new NotBlank()
                ],
                'label' => 'Minimal stress change',
                'label_attr' => ['class' => 'col-md-8']
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'relativemds_type';
    }

}
