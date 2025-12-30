<?php

namespace Base\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'form.username',
                'translation_domain' => 'FOSUserBundle',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'form.username'
                ]
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'form.name',
                'translation_domain' => 'FOSUserBundle',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.name']
            ])
            ->add('surname', TextType::class, [
                'required' => true,
                'label' => 'form.surname',
                'translation_domain' => 'FOSUserBundle',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.surname']
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.email',
                'translation_domain' => 'FOSUserBundle',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'form.email'
                ]
            ])
            ->add('organisation', TextType::class, [
                'required' => true,
                'label' => 'form.organisation',
                'translation_domain' => 'FOSUserBundle',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.organisation']
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'translation_domain' => 'FOSUserBundle',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control'
                    ],
                ],
                'first_options' => [
                    'label' => 'form.password',
                    'attr' => ['placeholder' => 'form.password']
                ],
                'second_options' => [
                    'label' => 'form.password_confirmation',
                    'attr' => ['placeholder' => 'form.password_confirmation']
                ],
                'invalid_message' => 'fos_user.password.mismatch',
            ]);
    }

    public function getParent()
    {
        return BaseType::class;
    }

    public function getBlockPrefix()
    {
        return 'base_user_registration';
    }
}
