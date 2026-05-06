<?php

namespace App\EventListener;

// src/App/EventListener/JWTInvalidListener.php
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;


class JWTInvalidListener
{
    /**
     * @param JWTInvalidEvent $event
     */
    public function onJWTInvalid(JWTInvalidEvent $event)
    {
        $response = new JWTAuthenticationFailureResponse('Votre Token n\'est pas valide, veuillez vous connecter à nouveau pour en obtenir un nouveau.', 401);
        $event->setResponse($response);
    }
}
