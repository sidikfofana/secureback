<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class UserInfoController extends AbstractController
{

    // public function __construct(
    //     private UserRepository $userRepo
    // ) {}

    // #[Route('/api/user/info', name: 'api_user_info', methods: ['GET'])]
    // public function getUserInfo(): JsonResponse
    // {
    //     $user = $this->getUser();

    //     if (!$user instanceof UserInterface) {
    //         return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
    //     }
        
    //     $userInfo = [
    //         'id' => $user->getId(),
    //         'usermail' => $user->getUserIdentifier(), // Or any other property you need
    //         'roles' => $user->getRoles(),
    //         'company_id' => $user->getCompany()->getId(),
    //         'company' => $user->getCompany()->getSlug() ?? "scb"
    //     ];

    //     return new JsonResponse($userInfo);
    // }

    #[Route('/api/user/info', name: 'api_user_info', methods: ['GET'])]
    public function getUserInfo(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $userInfo = [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(), // prénom
            'lastname' => $user->getName(),       // nom
            'email' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'company' => $user->getCompany()?->getSlug() ?? "scb",

            // champs supplémentaires
            'title'      => $user->getTitle(),
            'contact'    => $user->getContact(),
            'status'     => $user->isStatus(),
            'department' => $user->getDepartment()?->getName() ?? null,
            'create_at'  => $user->getCreateAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
        return $this->json($userInfo);
    }
}
