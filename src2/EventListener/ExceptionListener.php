<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class ExceptionListener
{

    public function __invoke(ExceptionEvent $event): void
    {

        // You get the exception object from the received event
        $exception = $event->getThrowable();
        $message = sprintf(
            'My Error says: %s with code: %s',
            $exception->getMessage(),
            $exception->getCode()
        );

        // Customize your response object to display the exception details
        $response = new JsonResponse();

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());

            $json_message = [
                'code' => $exception->getStatusCode(),
                'message' => $exception->getMessage()  ? $exception->getMessage() : Response::$statusTexts[$exception->getStatusCode()]
            ];
        } else {

            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $json_message = [
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $exception->getMessage()
            ];
            
        }

        $response->setData($json_message);
        // sends the modified response object to the event
        $event->setResponse($response);
    }
}
