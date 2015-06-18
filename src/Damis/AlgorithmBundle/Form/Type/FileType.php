<?php

namespace Damis\AlgorithmBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class FileType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('fileTitle', 'text', array(
                    'label' => 'Title',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => [
                        new NotBlank()
                    ]
                    ))
                ->add('fileDescription', 'textarea', array(
                    'label' => 'Description',
                    'required' => false,
                    'attr' =>
                        array(
                            'class' => 'form-control',
                            'rows' => 4,
                            'cols' => 40)))
                // File validation is past to Entity\File
                ->add('file', 'file', array(
                        'label' => 'File',
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Damis\AlgorithmBundle\Entity\File',
            'csrf_protection' => false,
            'translation_domain' => 'AlgorithmBundle'
        ));
    }

    public function getName()
    {
        return 'file_newtype';
    }
}
