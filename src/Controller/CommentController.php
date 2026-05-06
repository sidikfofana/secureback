<?php

namespace App\Controller;

use App\Entity\Comments;
use App\Entity\Tasks;
use App\Entity\User;
use App\Repository\TasksRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TasksRepository $tasksRepository,
        private UserRepository $userRepository,
        private ValidatorInterface $validator,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    #[Route('/api/tasks/comments/list', name: 'list_comments', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $comments = $this->entityManager->getRepository(Comments::class)
            ->findBy([], ['created_at' => 'DESC']);

        return $this->json([
            'status' => 'success',
            'data' => $comments
        ], Response::HTTP_OK, [], ['groups' => 'comments']);
    }

    #[Route('/api/tasks/comments/create/{id}', name: 'create_comment', methods: ['POST'])]
    public function create(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            // Champs obligatoires
            $requiredFields = ['content'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }

            // Vérifier la tâche
            $task = $this->tasksRepository->find($id);
            if (!$task) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Task not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier si l'utilisateur est connecté
            $user = $this->tokenStorage->getToken()?->getUser();
            if (!$user || !is_object($user)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Créer le commentaire
            $comment = new Comments();
            $comment->setTaskId($task);
            $comment->setUserId($user);
            $comment->setContent($data['content']);
            $comment->setCreatedAt(new \DateTimeImmutable());

            // Validation
            $errors = $this->validator->validate($comment);
            if (count($errors) > 0) {
                return $this->json([
                    'status' => 'error',
                    'message' => (string)$errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Comment created successfully',
                'data' => [
                    'id' => $comment->getId(),
                    'task_id' => $task->getId(),
                    'user_id' => $user->getId(),
                    'content' => $comment->getContent(),
                    'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/tasks/comments/update/{id}', name: 'update_comment', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $comment = $this->entityManager->getRepository(Comments::class)->find($id);
            if (!$comment) {
                return $this->json(['status' => 'error', 'message' => 'Comment not found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            if (isset($data['task_id'])) {
                $task = $this->tasksRepository->find($data['task_id']);
                if (!$task) {
                    return $this->json(['status' => 'error', 'message' => 'Task not found'], Response::HTTP_NOT_FOUND);
                }
                $comment->setTaskId($task);
            }

            if (isset($data['user_id'])) {
                $user = $this->userRepository->find($data['user_id']);
                if (!$user) {
                    return $this->json(['status' => 'error', 'message' => 'User not found'], Response::HTTP_NOT_FOUND);
                }
                $comment->setUserId($user);
            }

            if (isset($data['content'])) {
                $comment->setContent($data['content']);
            }

            $errors = $this->validator->validate($comment);
            if (count($errors) > 0) {
                return $this->json(['status' => 'error', 'message' => (string)$errors], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Comment updated successfully',
                'data' => [
                    'id' => $comment->getId(),
                    'task_id' => $comment->getTaskId()?->getId(),
                    'user_id' => $comment->getUserId()?->getId(),
                    'content' => $comment->getContent(),
                    'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/tasks/comments/delete/{id}', name: 'delete_comment', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $comment = $this->entityManager->getRepository(Comments::class)->find($id);
            if (!$comment) {
                return $this->json(['status' => 'error', 'message' => 'Comment not found'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($comment);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Comment deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/comments/by-task/{taskId}', name: 'comments_by_task', methods: ['GET'])]
    public function getByTask(int $taskId): JsonResponse
    {
        $task = $this->tasksRepository->find($taskId);
        if (!$task) {
            return $this->json(['status' => 'error', 'message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $comments = $this->entityManager->getRepository(Comments::class)
            ->findBy(['task_id' => $task], ['created_at' => 'DESC']);

        return $this->json([
            'status' => 'success',
            'data' => $comments
        ], Response::HTTP_OK, [], ['groups' => 'comments']);
    }

    //Get comments for a task
    #[Route('/api/tasks/comments/list/{id}', name: 'app_task_comments_list', methods: ['GET'])]
    public function getComments(int $id, TasksRepository $tasksRepo): JsonResponse
    {
        $task = $tasksRepo->find($id);

        if (!$task) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Tâche introuvable',
            ], 404);
        }

        // Récupérer et trier les commentaires (du plus récent au plus ancien)
        $comments = $task->getComments()->toArray();
        usort($comments, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        // Construire la réponse
        $commentsData = array_map(fn($comment) => [
            'id' => $comment->getId(),
            'author' => $comment->getUserId()?->getUserIdentifier(),
            'message' => $comment->getContent(),
            'date' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $comments);

        return new JsonResponse([
            'status' => 'success',
            'data' => $commentsData,
        ]);
    }

}
