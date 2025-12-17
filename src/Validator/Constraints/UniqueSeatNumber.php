<?php

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class UniqueSeatNumber extends Constraint
{
    public string $message = 'Le numéro de siège "{{ value }}" est déjà utilisé pour cet événement.';
    
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}


