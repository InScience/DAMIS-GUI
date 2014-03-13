<?php

namespace Damis\DatasetsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DatasetType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('title', 'text', array(
                    'label' => 'Title',
                    'required' => true,
                    'attr' => array('class' => 'form-control')
                    ))
                ->add('description', 'textarea', array(
                    'label' => 'Description',
                    'required' => false,
                    'attr' =>
                        array(
                            'class' => 'form-control',
                            'rows' => 4,
                            'cols' => 40)))
                ->add('file', 'file',
                    array(
                        'label' => 'File',
                        'attr' => array()
                    ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Damis\EntitiesBundle\Entity\Dataset',
            'translation_domain' => 'DatasetsBundle'
        ));
    }

    public function getName() {
        return 'datasets_newtype';
    }

}
