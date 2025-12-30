<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class PcaType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dataValidator = function ($object, ExecutionContextInterface $context) {
            $pca = $_POST['pca_type'] ?? null;
            
            if ($pca && isset($pca['projType']) && $pca['projType'] == 1 && ($object <= 0 || $object > 100)) {
                $context->addViolation('Relative cumulative variance must be in interval (0; 100]', [], null);
            }
        };

        $dataValidator2 = function ($object, ExecutionContextInterface $context) {
            $pca = $_POST['pca_type'] ?? null;
            
            if ($pca && isset($pca['projType']) && $pca['projType'] == 0 && ($object <= 0)) {
                $context->addViolation('This value type should be integer', [], null);
            }
        };

        $builder
        ->add('projType', ChoiceType::class, [
                'required' => false,
                'placeholder' => false,
                'data' => 0,
                'expanded' => true,
                'choices' => ['Space' => 0, 'Attribute relative cumulative variance' => 1],
                'constraints' => [
                    new NotBlank()
                ],
                'label' => 'Choose PCA projection',
                'label_attr' => ['class' => 'col-md-9']
            ])
        ->add('d', IntegerType::class, [
                'required' => true,
                'data' => 2,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'invalid_message' => 'This value type should be integer',
                'constraints' => [
                    new NotBlank(),
                    new Assert\Type(['type' => 'integer', 'message' => 'This value type should be integer']),
                    new Callback($dataValidator),
                    new Callback($dataValidator2)
                ],
                'label' => 'Space/Variance',
                'label_attr' => ['class' => 'col-md-9']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['choices', 'class']);
        
        $resolver->setDefaults([
            'translation_domain' => 'ExperimentBundle',
            'choices' => [],
            'class' => [],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'pca_type';
    }
}