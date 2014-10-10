<?php

namespace Damis\DatasetsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DatasetType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('datasetTitle', 'text', array(
                    'label' => 'Title',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => [
                        new NotBlank()
                    ]
                    ))
                ->add('datasetDescription', 'textarea', array(
                    'label' => 'Description',
                    'required' => false,
                    'attr' =>
                        array(
                            'class' => 'form-control',
                            'rows' => 4,
                            'cols' => 40)))
                // File validation is past to Entity\Dataset
                ->add('file', 'file', array(
                        'label' => 'File',
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Damis\DatasetsBundle\Entity\Dataset',
            'csrf_protection' => false,
            'translation_domain' => 'DatasetsBundle'
        ));
    }

    public function getName() {
        return 'datasets_newtype';
    }

}
