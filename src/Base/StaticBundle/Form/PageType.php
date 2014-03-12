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
            ->add('name')
            ->add('text', 'textarea', array('required' => false, 'attr' => array('class' => 'tinymce_textarea')))
            ->add('type', 'choice', array('choices' => Page::$types))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Base\StaticBundle\Entity\Page',
            'translation_domain' => 'FOSUserBundle'
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
