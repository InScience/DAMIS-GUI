<?php

namespace Damis\AlgorithmBundle\Form\Validators;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FileExtension extends Constraint
{
    public $invalid_type = 'Please upload a valid type';

    public function validatedBy()
    {
        return static::class.'Validator';
    }
}
