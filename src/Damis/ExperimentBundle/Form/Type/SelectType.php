<?php

namespace Damis\ExperimentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SelectType extends AbstractType
{
    protected $options = [];
    
    private static function debug($msg) {
        file_put_contents('/tmp/select_debug.log', date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dataValidator = function ($object, ExecutionContextInterface $context) {
            $data = $_POST['select_type'] ?? null;
            
            if ($data && !isset($data['selAttr'])) {
                $context->addViolation('Please select attributes', [], null);
            }
        };

        $this->options = $options;

        $builder
            ->add('attr', ChoiceType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'choices' => $options['class'] ?? [],
                'multiple' => true,
                'label' => 'Attributes'
            ])
            ->add('selAttr', ChoiceType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'choices' => [],
                'multiple' => true,
                'constraints' => [
                    new Callback($dataValidator)
                ],
                'label' => 'Selected attributes'
            ])
            ->add('classAttr', ChoiceType::class, [
                'required' => false,
                'placeholder' => '',
                'attr' => ['class' => 'form-control'],
                'choices' => $options['class'] ?? [],
                'label' => 'Class attribute'
            ]);

        $closure = function (FormEvent $e) {
            $data = $e->getData();
            $form = $e->getForm();
            $choices_selAttr = [];
            
            self::debug('=== SelectType PRE_SUBMIT Event ===');
            self::debug('Submitted data: ' . json_encode($data));
            self::debug('Available class options: ' . json_encode($this->options['class'] ?? []));
            
            // Always rebuild classAttr with all available choices to prevent validation errors
            $allChoices = $this->options['class'] ?? [];
            ksort($allChoices);
            
            self::debug('Rebuilding classAttr with choices: ' . json_encode($allChoices));
            
            $form->remove('classAttr');
            $form->add('classAttr', ChoiceType::class, [
                'required' => false,
                'placeholder' => '',
                'attr' => ['class' => 'form-control'],
                'choices' => $allChoices,
                'label' => 'Class attribute'
            ]);
            
            if (isset($data['selAttr']) && is_array($data['selAttr'])) {
                self::debug('Processing selAttr: ' . json_encode($data['selAttr']));
                
                // Build a reverse lookup: value => name
                $valueToName = array_flip($this->options['class'] ?? []);
                self::debug('Value to name mapping: ' . json_encode($valueToName));
                
                foreach ($data['selAttr'] as $val) {
                    self::debug('  Checking val: ' . json_encode($val) . ' (type: ' . gettype($val) . ')');
                    
                    // Convert to int for lookup since form values are strings but array keys are ints
                    $intVal = (int) $val;
                    
                    // Find the attribute name that has this value
                    if (isset($valueToName[$intVal])) {
                        $attrName = $valueToName[$intVal];
                        $choices_selAttr[$attrName] = $intVal;
                        self::debug('    Found: ' . $attrName . ' => ' . $intVal);
                    } else {
                        self::debug('    NOT found in valueToName for val: ' . $val);
                    }
                }
                
                self::debug('Final choices_selAttr: ' . json_encode($choices_selAttr));
                
                $form->remove('selAttr');
                ksort($choices_selAttr);
                
                $form->add('selAttr', ChoiceType::class, [
                    'attr' => ['class' => 'form-control'],
                    'choices' => $choices_selAttr,
                    'multiple' => true,
                    'label' => 'Selected attributes'
                ]);

                // Calculate remaining attributes
                $choices_attr = array_diff($this->options['class'] ?? [], $choices_selAttr);
                ksort($choices_attr);
                
                self::debug('Final choices_attr: ' . json_encode($choices_attr));
                
                $form->remove('attr');
                $form->add('attr', ChoiceType::class, [
                    'attr' => ['class' => 'form-control'],
                    'choices' => $choices_attr,
                    'multiple' => true,
                    'label' => 'Attributes'
                ]);
            } else {
                self::debug('selAttr not set or not an array in submitted data');
            }
        };

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $closure);
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
        return 'select_type';
    }
}