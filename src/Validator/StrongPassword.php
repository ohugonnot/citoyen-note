<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class StrongPassword extends Constraint
{
    public string $message = 'Mot de passe invalide : {{ errors }}';
}
