<?php

namespace Base\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\ResettingFormType as BaseType;

class ResettingFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('new', 'repeated', array(
            'type' => 'password',
            'options' => array('translation_domain' => 'FOSUserBundle'),
            'first_options' => array('label' => 'form.new_password', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.new_password'),),
            'second_options' => array('label' => 'form.new_password_confirmation', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.new_password_confirmation'),),
            'invalid_message' => 'fos_user.password.mismatch',
        ));
    }

    public function getName()
    {
        return 'base_user_resetting';
    }
}
