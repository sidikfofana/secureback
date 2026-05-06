<?php

namespace App\EventSubscriber;

use App\Controller\GTP\Sold\CheckSoldController;
use App\Controller\GTP\Transactions\CarteToCarteController;
use App\Controller\GTP\Transactions\CarteToWalletController;
use App\Controller\MobileMoney\Wave\Payout\CreatePayoutBatchController;
use App\Controller\MobileMoney\Wave\Payout\CreatePayoutController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimiterSubscriber implements EventSubscriberInterface
{

    public function __construct(RateLimiterFactory $anonymousApiLimiter)
    {
        $this->anonymousApiLimiter = $anonymousApiLimiter;
    }

    /**
     * @var RateLimiterFactory
     */
    private $anonymousApiLimiter;

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
            KernelEvents::CONTROLLER => 'onKernelController'
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        // Retrieve the request from the request event
        $request = $event->getRequest();

        // Check if the requested route name contains api_
        if (strpos($request->get("_route"), 'api_') !== false) {
            // Retrieve the limiter based on the request client IP
            $limiter = $this->anonymousApiLimiter->create($request->getClientIp());

            // Consume one request and check if it's still accepted
            if (false === $limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }
        }
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if (
            $controller instanceof CarteToWalletController
            || $controller instanceof CarteToCarteController
            || $controller instanceof CreatePayoutController
            || $controller instanceof CreatePayoutBatchController
            || $controller instanceof CheckSoldController
        ) {

            $identifier = $event->getRequest()->getClientIp();
            $limiter = $this->anonymousApiLimiter->create($identifier);
            // $limit = $limiter->consume();

            // $headers = [
            //     'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
            //     'X-RateLimit-Retry-After' => $limit->calculateTimeForTokens(1, 1),
            //     'X-RateLimit-Limit' => $limit->getLimit(),
            // ];
    
            // if (false === $limit->isAccepted()) {
            //     return new Response(null, Response::HTTP_TOO_MANY_REQUESTS, $headers);
            // }

            // Consume one request and check if it's still accepted
            if (false === $limiter->consume(1)->isAccepted()) {
                //TODO send Email

                throw new TooManyRequestsHttpException();
            }
        }
    }
}
