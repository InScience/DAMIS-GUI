<?php

namespace Base\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, array('label' => 'form.username', 'translation_domain' => 'FOSUserBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.username'),))
            ->add('name', null, array('label' => 'form.name', 'translation_domain' => 'FOSUserBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.name'),))
            ->add('surname', null, array('label' => 'form.surname', 'translation_domain' => 'FOSUserBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.surname'),))
            ->add('email', 'email', array('label' => 'form.email', 'translation_domain' => 'FOSUserBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.email'),))
            ->add('organisation', null, array('label' => 'form.organisation', 'translation_domain' => 'FOSUserBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.organisation'),))
            ->add('roles', 'choice', array(
                'required' => false,
                'translation_domain' => 'FOSUserBundle',
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->refactorRoles(),
                'attr' => array('class' => 'checkbox'),
            ))
            ->add('locked', null, array('label' => 'form.locked', 'translation_domain' => 'FOSUserBundle', 'required' => false,))
        ;
    }

    private function refactorRoles()
    {
        //$result['ROLE_USER'] = 'admin.role_user'; //negalima panaikinti vartotojui tokios roles
        $result['ROLE_ADMIN'] = 'admin.role_admin';
        return $result;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Base\UserBundle\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'base_userbundle_user';
    }
}
