<?php

namespace Base\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class ChangePasswordFormType extends AbstractType
{
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $constraint = new UserPassword();

        $builder->add('current_password', PasswordType::class, [
            'label' => 'form.current_password',
            'translation_domain' => 'FOSUserBundle',
            'mapped' => false,
            'constraints' => $constraint,
            'attr' => ['class' => 'form-control', 'placeholder' => 'form.current_password']
        ]);

        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'options' => ['translation_domain' => 'FOSUserBundle', 'attr' => ['autocomplete' => 'new-password']],
            'first_options' => ['label' => 'form.new_password', 'attr' => ['class' => 'form-control', 'placeholder' => 'form.new_password']],
            'second_options' => ['label' => 'form.new_password_confirmation', 'attr' => ['class' => 'form-control', 'placeholder' => 'form.new_password_confirmation']],
            'invalid_message' => 'fos_user.password.mismatch'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->class,
            'csrf_token_id' => 'change_password',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'base_user_change_password';
    }
}