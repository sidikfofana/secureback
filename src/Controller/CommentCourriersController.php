<?php

namespace App\Controller;

use App\Entity\CommentCourriers;
use App\Helpers\Helpers;
use App\Repository\CourriersRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CommentCourriersController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private Helpers $Helpers,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private TokenStorageInterface $tokenStorage,
        private UserRepository $userRepository,
    ) {
    } 

    //Create comment for a courrier
    #[Route('/api/courriers/comments/{id}', name: 'app_add_comment', methods: ['POST'])]
    public function addComment(
        int $id,
        Request $request,
        CourriersRepository $courrierRepo,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
    ): JsonResponse {
        try {

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }
            $message = $data['message'];

            // Champs obligatoires
            $requiredFields = ['message'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }

            // Vérifier si l'utilisateur est connecté
            $connectedUser = $tokenStorage->getToken()?->getUser();
            if (!$connectedUser || !is_object($connectedUser)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié',
                ], 401);
            }

            // Récupérer l’utilisateur qui envoie le document (celui indiqué dans form-data)
            $user = $this->tokenStorage->getToken()?->getUser();
            if (!$user || !is_object($user)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $courrier = $courrierRepo->find($id);
            if (!$courrier) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Courrier introuvable',
                ], 404);
            }

            if (!$message) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Le champ message est requis',
                ], 400);
            }

            $comment = new CommentCourriers();
            $comment->setMessage($message);
            $comment->setAuthor($user);
            $comment->setCourrier($courrier);
            $comment->setCreatedAt(new \DateTimeImmutable());

            $em->persist($comment);
            $em->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Commentaire ajouté avec succès',
                'data' => [
                    'id' => $comment->getId(),
                    'author' => $user->getUserIdentifier(),
                    'message' => $comment->getMessage(),
                    'date' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //Get comments for a courrier
    #[Route('/api/courriers/comments/list/{id}', name: 'app_get_comments', methods: ['GET'])]
    public function getComments(
        int $id,
        CourriersRepository $courrierRepo
    ): JsonResponse {
        $courrier = $courrierRepo->find($id);

        if (!$courrier) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Courrier introuvable',
            ], 404);
        }

        // Récupérer les commentaires et les trier par createdAt DESC
        $comments = $courrier->getComments()->toArray(); // convertir en tableau
        usort($comments, function($a, $b) {
            return $b->getCreatedAt()->getTimestamp() <=> $a->getCreatedAt()->getTimestamp();
        });

        $commentsData = [];
        foreach ($courrier->getComments() as $comment) {
            $commentsData[] = [
                'id' => $comment->getId(),
                'author' => $comment->getAuthor()?->getUserIdentifier(),
                'message' => $comment->getMessage(),
                'date' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => $commentsData,
        ]);
    }

}
