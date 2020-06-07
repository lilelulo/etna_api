<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        if ($event->getException() instanceof NotFoundHttpException) {
            $response = new JsonResponse();
            $response->setData(['message' => 'Not found']);
            $event->setResponse($response);
        }
        if ($event->getException() instanceof AccessDeniedHttpException) {
            $response = new JsonResponse();
            $response->setData(['message' => 'Forbiden']);
            $event->setResponse($response);
        }
        return $event;
    }
}