<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class KmeansType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

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
            'label_attr' => ['class' => 'col-md-9']
        ])->add('kMax', 'integer', [
            'required' => true,
            'data' => 10,
            'attr' => array('class' => 'form-control', 'min' => 1, 'max' => 100),
            'constraints' => [
                new Assert\Range([
                    'min' => 1,
                    'max' => 100,
                    'minMessage' => 'Number of cluster must be in interval [1; 100]',
                    'maxMessage' => 'Number of cluster must be in interval [1; 100]'
                ]),
                new NotBlank(),
                new Assert\Type(array(
                    'type' => 'integer',
                    'message' => 'This value type should be integer'
                ))
            ],
            'label' => 'Maximum number of cluster',
            'label_attr' => ['class' => 'col-md-9']
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'kmeans_type';
    }

}
