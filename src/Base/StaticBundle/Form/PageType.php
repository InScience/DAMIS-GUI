<?php

namespace Base\StaticBundle\Form;

use Base\StaticBundle\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PageType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, array('label' => 'form.title', 'translation_domain' => 'StaticBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.title'),))
            ->add('groupName', 'choice',
                array(
                    'choices' => array('help' => 'form.help'),
                    'required' => false, 'label' => 'form.group', 'translation_domain' => 'StaticBundle',
                    'attr' => array('class' => 'form-control', 'placeholder' => 'form.group'),
                )
            )
            ->add('text', 'textarea', array('label' => 'form.text', 'required' => false, 'attr' => array('class' => 'tinymce_textarea form-control')))
            ->add('position', 'integer', array('label' => 'form.position', 'translation_domain' => 'StaticBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.position'),))
            ->add('language', 'choice',
                array(
                    'choices' => array('lt_LT' => 'form.lt_LT', 'en_US' => 'form.en_US'),
                    'label' => 'form.language', 'translation_domain' => 'StaticBundle',
                    'attr' => array('class' => 'form-control', 'placeholder' => 'form.position'),
                )
            )
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Base\StaticBundle\Entity\Page',
            'translation_domain' => 'StaticBundle'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'base_staticbundle_page';
    }
}
