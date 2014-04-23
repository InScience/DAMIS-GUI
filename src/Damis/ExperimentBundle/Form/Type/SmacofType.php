<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class SmacofType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
        ->add('zeidel', 'choice', [
                'empty_value' => false,
                'required' => false,
                'data' => 0,
                'expanded' => true,
                'choices' => array(
                    0 => 'No',
                    1 => 'Yes'
                ),
                'constraints' => [
                    new NotBlank()
                ],
                'label' => 'Does apply Seidel modification?',
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
                'label_attr' => ['class' => 'col-md-9']
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
                'label_attr' => ['class' => 'col-md-9']
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'smacof_type';
    }

}
