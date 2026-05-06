<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TestController
{
    #[Route('/api/test', name: 'api_test')]
    public function test(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
