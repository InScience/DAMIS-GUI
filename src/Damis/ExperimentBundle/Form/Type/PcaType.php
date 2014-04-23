<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class PcaType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $dataValidator = function($object, ExecutionContextInterface $context) use ($builder) {
            $pca = $_POST['pca_type'];
            if($pca['projType'] == 1 && ($object <= 0 || $object > 100)) {
                $context->addViolation('Relative cumulative variance must be in interval (0; 100]', [], null);
            }
        };
        $dataValidator2 = function($object, ExecutionContextInterface $context) use ($builder) {
            $pca = $_POST['pca_type'];
            if($pca['projType'] == 0 && ($object <= 0)) {
                $context->addViolation('This value type should be integer', [], null);
            }
        };
        $builder
        ->add('projType', 'choice', [
                'required' => false,
                'empty_value' => false,
                'data' => 0,
                'expanded' => true,
                'choices' => array(
                    0 => 'Space',
                    1 => 'Attribute relative cumulative variance'
                ),
                'constraints' => [
                    new NotBlank()
                ],
                'label' => 'Choose PCA projection',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('d', 'integer', [
                'required' => true,
                'data' => 2,
                'attr' => array('class' => 'form-control'),
                'invalid_message' => 'This value type should be integer',
                'constraints' => [
                    new NotBlank(),
                    new Assert\Type(array(
                        'type' => 'integer',
                        'message' => 'This value type should be integer'
                    )),
                    new Callback([$dataValidator]),
                    new Callback([$dataValidator2])
                ],
                'label' => 'Space/Variance',
                'label_attr' => ['class' => 'col-md-9']
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'pca_type';
    }

}
