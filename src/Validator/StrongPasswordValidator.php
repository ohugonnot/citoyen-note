<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class StrongPasswordValidator extends ConstraintValidator
{
    private const MIN_LENGTH = 8;
    private const WEAK_PASSWORDS = [
        '12345678', 'password', 'password1!', 'azerty123', 'qwerty123',
        'motdepasse', '00000000', '11111111', '123456789', 'password123',
    ];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, StrongPassword::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $errors = [];

        if (strlen($value) < self::MIN_LENGTH) {
            $errors[] = 'au moins 8 caractères';
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'au moins une majuscule';
        }

        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = 'au moins une minuscule';
        }

        if (!preg_match('/\d/', $value)) {
            $errors[] = 'au moins un chiffre';
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $value)) {
            $errors[] = 'au moins un caractère spécial';
        }

        if (in_array(strtolower($value), self::WEAK_PASSWORDS, true)) {
            $errors[] = 'mot de passe trop courant';
        }

        if (!empty($errors)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ errors }}', implode(', ', $errors))
                ->addViolation();
        }
    }
}
