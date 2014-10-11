<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class RdfType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('q', 'number', [
                'required' => true,
                'data' => 0.63,
                'attr' => array('class' => 'form-control'),
                'constraints' => [
                    new NotBlank(),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 1,
                        'minMessage' => 'Resistance to noise must be in interval (0; 1]',
                        'maxMessage' => 'Resistance to noise must be in interval (0; 1]'
                    ]),
                ],
                'label' => 'Resistance to noise',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('dL', 'integer', [
                'required' => true,
                'data' => 80,
                'attr' => array('class' => 'form-control', 'min' => 0, 'max' => 100),
                'constraints' => [
                    new Assert\Range([
                        'min' => 0,
                        'max' => 100,
                        'minMessage' => 'Number of percents in interval [0; 100]',
                        'maxMessage' => 'Number of percents in interval [0; 100]'
                    ]),
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    ))
                ],
                'label' => 'Size of training data',
                'label_attr' => ['class' => 'col-md-8']
            ])
        ->add('dT', 'integer', [
                'required' => true,
                'attr' => array('class' => 'form-control', 'min' => 0, 'max' => 100),
                'read_only' => true,
                'data' => 20,
                'constraints' => [
                    new Assert\Range([
                        'min' => 0,
                        'max' => 100,
                        'minMessage' => 'Number of percents in interval [0; 100]',
                        'maxMessage' => 'Number of percents in interval [0; 100]'
                    ]),
                    new NotBlank()
                ],
                'label' => 'Size of test data',
                'label_attr' => ['class' => 'col-md-8']
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'rdf_type';
    }

}
