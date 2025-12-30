<?php

namespace Base\StaticBundle\Form;

use Base\StaticBundle\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Base\StaticBundle\Entity\LanguageEnum;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

class PageType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'form.title', 'translation_domain' => 'StaticBundle', 'attr' => ['class' => 'form-control', 'placeholder' => 'form.title']])
            ->add('groupName', ChoiceType::class, ['choices' => ['form.help' => 'help', 'form.front_page' => 'front_page'], 'required' => false, 'label' => 'form.group', 'translation_domain' => 'StaticBundle', 'attr' => ['class' => 'form-control', 'placeholder' => 'form.group']])
            ->add('text', TextareaType::class, ['label' => 'form.text', 'required' => false, 'attr' => ['class' => 'tinymce_textarea form-control']])
            ->add('position', IntegerType::class, ['label' => 'form.position', 'translation_domain' => 'StaticBundle', 'attr' => ['class' => 'form-control', 'placeholder' => 'form.position']])
            ->add('language', EnumType::class, [
                'class' => LanguageEnum::class,
                'label' => 'form.language',
                'translation_domain' => 'StaticBundle',
                'attr' => ['class' => 'form-control'],
                'choice_label' => function(LanguageEnum $choice): string {
                    return match ($choice) {
                        LanguageEnum::LITHUANIAN => 'form.lt_LT',
                        LanguageEnum::ENGLISH => 'form.en_US',
                    };
                },
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => Page::class, 'translation_domain' => 'StaticBundle']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'base_staticbundle_page';
    }
}