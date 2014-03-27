<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class SelectType extends AbstractType {

    protected $options = [];

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $dataValidator = function($object, ExecutionContextInterface $context) use ($builder) {
            $data = $_POST['select_type'];
            if(!isset($data['selAttr'])) {
                $context->addViolation('Please select attributes', [], null);
            }
        };

        $this->options = $options;

        $builder
        ->add('attr', 'choice', [
                'attr' => array('class' => 'form-control'),
                'choices' => $options['data']['class'],
                'multiple' => true,
                'label' => 'Attributes'
            ])
        ->add('selAttr', 'choice', [
                'attr' => array('class' => 'form-control'),
                'choices' => array(),
                'multiple' => true,
                'constraints' => [
                    new Callback([$dataValidator])
                ],
                'label' => 'Selected attributes'
            ])
        ->add('classAttr', 'choice', [
                'empty_value' => '',
                'attr' => array('class' => 'form-control'),
                'choices' => $options['data']['class'],
                'label' => 'Class attribute'
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $e) {
            $data = $e->getData();
            $form = $e->getForm();
            $choices_selAttr = [];
            if(isset($data['selAttr'])){
                foreach($data['selAttr'] as $val)
                    $choices_selAttr[$val] = $this->options['data']['class'][$val];
                $form->remove('selAttr');
                ksort($choices_selAttr);
                $form->add('selAttr', 'choice', [
                    'attr' => array('class' => 'form-control'),
                    'choices' => $choices_selAttr,
                    'multiple' => true,
                    'label' => 'Selected attributes'
                ]);
                $choices_attr = array_diff($this->options['data']['class'], $choices_selAttr);
                ksort($choices_attr);
                $form->remove('attr');
                $form->add('attr', 'choice', [
                    'attr' => array('class' => 'form-control'),
                    'choices' => $choices_attr,
                    'multiple' => true,
                    'label' => 'Attributes'
                ]);
            }
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'translation_domain' => 'ExperimentBundle'
        ));
    }

    public function getName() {
        return 'select_type';
    }

}
