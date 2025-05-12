<?php
// src/EventSubscriber/ApiExceptionSubscriber.php

namespace App\EventSubscriber;

use App\Exception\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Validator\ConstraintViolationInterface;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        /* --------------------------------------------------------------------
         * 422 Unprocessable Entity – Validierungsfehler
         * ------------------------------------------------------------------ */
        if ($e instanceof ValidationException) {
            $errors = [];

            /** @var ConstraintViolationInterface $v */
            foreach ($e->getViolations() as $v) {
                $errors[$v->getPropertyPath()][] = $v->getMessage();
            }

            $event->setResponse(new JsonResponse([
                'message' => $e->getMessage(),
                'errors'  => $errors,
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));

            return; // nichts weiter propagieren
        }

        /* --------------------------------------------------------------------
         * 400 Bad Request – Deserialisierungs-/Typfehler (z. B. null → DateTime)
         * ------------------------------------------------------------------ */
        if ($e instanceof NotNormalizableValueException) {
            $event->setResponse(new JsonResponse([
                'message' => 'Invalid input',
                'detail'  => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST));

            return;
        }

        /* --------------------------------------------------------------------
         * Weitere Fehlerklassen nach Bedarf an dieser Stelle abfangen …
         * ------------------------------------------------------------------ */
    }
}
