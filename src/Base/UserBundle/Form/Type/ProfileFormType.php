<?php

namespace Base\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FOS\UserBundle\Form\Type\ProfileFormType as BaseProfileFormType;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class ProfileFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.name',
                'translation_domain' => 'FOSUserBundle',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.name']
            ])
            ->add('surname', TextType::class, [
                'label' => 'form.surname',
                'translation_domain' => 'FOSUserBundle',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.surname']
            ])
            ->add('organisation', TextType::class, [
                'label' => 'form.organisation',
                'translation_domain' => 'FOSUserBundle',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.organisation']
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return BaseProfileFormType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'base_user_profile';
    }
}
