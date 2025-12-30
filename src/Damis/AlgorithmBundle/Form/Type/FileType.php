<?php

namespace Damis\AlgorithmBundle\Form\Type;

use Damis\AlgorithmBundle\Entity\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType as SymfonyFileType;
use Symfony\Component\Validator\Constraints\NotBlank;

class FileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fileTitle', TextType::class, [
                'label' => 'Title',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('fileDescription', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                ],
            ]);

        if ($options['is_edit'] === false) {
            $builder->add('file', SymfonyFileType::class, [
                'label' => 'File',
                // This field is not directly mapped to a database column,
                // so we must tell Symfony that. The controller handles the upload.
                'mapped' => false,
                'required' => true, // A file is required when creating
                'constraints' => [
                    new NotBlank(['message' => 'Please upload an algorithm file.']),
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => File::class,
            'translation_domain' => 'AlgorithmBundle',
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'file';
    }
}