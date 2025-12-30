<?php

namespace Base\UserBundle\Form;

use Base\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, ['label' => 'form.username', 'translation_domain' => 'FOSUserBundle', 'attr' => ['class' => 'form-control', 'placeholder' => 'form.username']])
            ->add('name', TextType::class, ['label' => 'form.name', 'translation_domain' => 'FOSUserBundle', 'attr' => ['class' => 'form-control', 'placeholder' => 'form.name']])
            ->add('surname', TextType::class, ['label' => 'form.surname', 'translation_domain' => 'FOSUserBundle', 'attr' => ['class' => 'form-control', 'placeholder' => 'form.surname']])
            ->add('email', EmailType::class, ['label' => 'form.email', 'translation_domain' => 'FOSUserBundle', 'attr' => ['class' => 'form-control', 'placeholder' => 'form.email']])
            ->add('organisation', TextType::class, ['label' => 'form.organisation', 'translation_domain' => 'FOSUserBundle', 'attr' => ['class' => 'form-control', 'placeholder' => 'form.organisation']])
            ->add('roles', ChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'admin.role_confirmed' => 'ROLE_CONFIRMED',
                    'admin.role_admin' => 'ROLE_ADMIN',
                ],
                'choice_translation_domain' => 'FOSUserBundle',
                'attr' => ['class' => 'checkbox']
            ])
            ->add('locked', CheckboxType::class, ['label' => 'form.locked', 'translation_domain' => 'FOSUserBundle', 'required' => false])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'base_userbundle_user';
    }
}