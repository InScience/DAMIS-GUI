<?php

namespace Damis\DatasetsBundle\Form\Type;

use Damis\DatasetsBundle\Entity\Dataset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class DatasetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('datasetTitle', TextType::class, [
                'label' => 'Title',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('datasetDescription', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'cols' => 40
                ]
            ])
            ->add('file', FileType::class, [
                'label' => 'File',
                'mapped' => false, 
                'required' => false, 
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Dataset::class,
            'csrf_protection' => false,
            'translation_domain' => 'DatasetsBundle'
        ]);
    }

    public function getBlockPrefix()
    {
        return 'datasets_newtype';
    }
}