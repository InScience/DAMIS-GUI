<?php

namespace Damis\DatasetsBundle\Form\Validators;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FileExtension extends Constraint
{
    public $invalid_type = 'Please upload a valid type';

    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}
