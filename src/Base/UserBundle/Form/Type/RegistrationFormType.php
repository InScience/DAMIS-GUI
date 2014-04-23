<?php

namespace Base\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;

class RegistrationFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

/*        įvesti savo vardą, įvesti savo pavardę,
        Dabar svetainėje reikia įvesti tik naudotoją ir el. paštą.*/


        $builder
            ->add('username', null, array('required' => 'true', 'label' => 'form.username', 'translation_domain' => 'FOSUserBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.username')))
            ->add('name', null, array('required' => 'true', 'label' => 'form.name', 'translation_domain' => 'FOSUserBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.name'),))
            ->add('surname', null, array('required' => 'true', 'label' => 'form.surname', 'translation_domain' => 'FOSUserBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.surname'),))
            ->add('email', 'email', array('required' => 'true', 'label' => 'form.email', 'translation_domain' => 'FOSUserBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.email')))
            ->add('organisation', null, array('required' => 'true', 'label' => 'form.organisation', 'translation_domain' => 'FOSUserBundle', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.organisation'),))
            ->add('plainPassword', 'repeated', array(
                'type' => 'password',
                'options' => array('translation_domain' => 'FOSUserBundle'),
                'first_options' => array('label' => 'form.password', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.password')),
                'second_options' => array('label' => 'form.password_confirmation', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.password_confirmation')),
                'invalid_message' => 'fos_user.password.mismatch',
            ))
        ;
    }

    public function getName()
    {
        return 'base_user_registration';
    }
}