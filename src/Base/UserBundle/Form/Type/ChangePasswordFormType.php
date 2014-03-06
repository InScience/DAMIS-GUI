<?php

namespace Base\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword as OldUserPassword;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use FOS\UserBundle\Form\Type\ChangePasswordFormType as BaseType;

class ChangePasswordFormType extends BaseType {

    protected $container = null;

    public function __construct($modelUserClass = null, $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        if (class_exists('Symfony\Component\Security\Core\Validator\Constraints\UserPassword')) {
            $constraint = new UserPassword();
        } else {
            // Symfony 2.1 support with the old constraint class
            $constraint = new OldUserPassword();
        }

        $builder->add('current_password', 'password', array(
            'label' => 'form.current_password',
            'translation_domain' => 'FOSUserBundle',
            'mapped' => false,
            'constraints' => $constraint,
            'attr' => array('class' => 'form-control', 'placeholder' => 'form.current_password'),
        ));
        $builder->add('new', 'repeated', array(
            'type' => 'password',
            'options' => array('translation_domain' => 'FOSUserBundle'),
            'first_options' => array('label' => 'form.new_password', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.new_password'),),
            'second_options' => array('label' => 'form.new_password_confirmation', 'attr' => array('class' => 'form-control', 'placeholder' => 'form.new_password_confirmation'),),
            'invalid_message' => 'fos_user.password.mismatch',
        ));
    }

    public function getName() {
        return 'base_user_change_password';
    }

}
