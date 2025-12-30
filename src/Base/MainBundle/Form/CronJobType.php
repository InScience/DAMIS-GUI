<?php

namespace Base\MainBundle\Form;

use Base\MainBundle\Entity\CronJob;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CronJobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'cron.form.name',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('schedule', ChoiceType::class, [
                'label' => 'cron.form.schedule',
                'choices' => [
                    'cron.schedule.every_minute' => '* * * * *',
                    'cron.schedule.every_five_minutes' => '*/5 * * * *',
                    'cron.schedule.every_fifteen_minutes' => '*/15 * * * *',
                    'cron.schedule.every_thirty_minutes' => '*/30 * * * *',
                    'cron.schedule.hourly' => '0 * * * *',
                    'cron.schedule.daily' => '0 0 * * *',
                    'cron.schedule.weekly' => '0 0 * * 0',
                    'cron.schedule.monthly' => '0 0 1 * *',
                    'cron.schedule.custom' => 'custom',
                ],
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('scheduleCustom', TextType::class, [
                'label' => 'cron.form.schedule_custom',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '* * * * *',
                ],
            ])
            ->add('command', TextType::class, [
                'label' => 'cron.form.command',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '/usr/bin/php /path/to/app/bin/console command:name',
                ],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'cron.form.description',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false,
            ])
            ->add('logFile', TextType::class, [
                'label' => 'cron.form.log_file',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '/var/log/cron/job.log',
                ],
                'required' => false,
            ])
            ->add('errorFile', TextType::class, [
                'label' => 'cron.form.error_file',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '/var/log/cron/job-error.log',
                ],
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'cron.form.enabled',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CronJob::class,
            'translation_domain' => 'general',
        ]);
    }
}

