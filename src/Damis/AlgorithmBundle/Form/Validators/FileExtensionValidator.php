<?php

namespace Damis\AlgorithmBundle\Form\Validators;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FileExtensionValidator extends ConstraintValidator
{

    public function validate($value, Constraint $constraint)
    {

        if ($value !== null) {
            if (!$this->endsWith($value->getClientOriginalName(), '.zip')) {
                $this->context->addViolation($constraint->invalid_type);
            }
        }

    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}
