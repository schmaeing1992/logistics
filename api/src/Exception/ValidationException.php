<?php
// src/Exception/ValidationException.php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Wird geworfen, wenn die Symfony-Validator-Prüfung schlägt.
 * Der ApiExceptionSubscriber formatiert daraus die JSON-Antwort (422).
 */
class ValidationException extends \RuntimeException
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violations
    ) {
        parent::__construct('Validation failed');
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
