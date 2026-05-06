<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class JWTNotFoundListener
{

    /**
     * @param JWTNotFoundEvent $event
     */
    public function onJWTNotFound(JWTNotFoundEvent $event)
    {
       $data = [
            'status_code'  => 401,
            'message' => 'Token introuvable',
        ];

        $response = new JsonResponse($data, 403);
        $event->setResponse($response); 
    }
}
