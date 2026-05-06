<?php

namespace App\Controller;

use App\Entity\TimeEntries;
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

class TimeEntryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TasksRepository $tasksRepository,
        private UserRepository $userRepository,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/api/time-entries/list', name: 'list_time_entries', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $taskId = $request->query->get('task_id');
            $criteria = [];

            if ($taskId) {
                $task = $entityManager->getRepository(Tasks::class)->find($taskId);
                if (!$task) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Task not found',
                    ], Response::HTTP_NOT_FOUND);
                }

                $criteria['task'] = $task;
            }

            $entries = $entityManager->getRepository(TimeEntries::class)
                ->findBy($criteria, ['date' => 'DESC']);

            return $this->json([
                'status' => 'success',
                'count' => count($entries),
                'data' => $entries,
            ], Response::HTTP_OK, [], ['groups' => 'time_entries']);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/time-entries/create', name: 'create_time_entry', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            // Champs obligatoires
            $requiredFields = ['task_id', 'duration', 'comment'];
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
            $task = $this->tasksRepository->find($data['task_id']);
            if (!$task) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Time not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Utilisateur connecté qui crée la tâche
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Créer l’entrée de temps
            $entry = new TimeEntries();
            $entry->setTask($task);
            $entry->setUser($user);
            $entry->setDate(new \DateTimeImmutable());
            $entry->setDuration($data['duration']);
            $entry->setComment($data['comment']);

            // Validation
            $errors = $this->validator->validate($entry);
            if (count($errors) > 0) {
                return $this->json([
                    'status' => 'error',
                    'message' => (string)$errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($entry);
            $this->entityManager->flush();

            // Mise à jour du champ spent_time dans Tasks
            $totalDuration = 0;
            foreach ($task->getTimeEntries() as $te) {
                $totalDuration += (float)$te->getDuration();
            }
            $task->setSpentTime((int)$totalDuration);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Time entry created successfully',
                'data' => [
                    'id' => $entry->getId(),
                    'task_id' => $task->getId(),
                    'user_id' => $user->getId(),
                    'date' => $entry->getDate()->format('Y-m-d'),
                    'duration' => $entry->getDuration(),
                    'total_spent_time' => $totalDuration
                ]
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/time-entries/update/{id}', name: 'update_time_entry', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $entry = $this->entityManager->getRepository(TimeEntries::class)->find($id);
            if (!$entry) {
                return $this->json(['status' => 'error', 'message' => 'Time entry not found'], Response::HTTP_NOT_FOUND);
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
                $entry->setTaskId($task);
            }

            if (isset($data['user_id'])) {
                $user = $this->userRepository->find($data['user_id']);
                if (!$user) {
                    return $this->json(['status' => 'error', 'message' => 'User not found'], Response::HTTP_NOT_FOUND);
                }
                $entry->setUserId($user);
            }

            if (isset($data['date'])) $entry->setDate(new \DateTimeImmutable($data['date']));
            if (isset($data['duration'])) $entry->setDuration($data['duration']);
            if (isset($data['comment'])) $entry->setComment($data['comment']);

            $errors = $this->validator->validate($entry);
            if (count($errors) > 0) {
                return $this->json(['status' => 'error', 'message' => (string)$errors], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Time entry updated successfully',
                'data' => [
                    'id' => $entry->getId(),
                    'task_id' => $entry->getTask()?->getId(),
                    'user_id' => $entry->getUser()?->getId(),
                    'duration' => $entry->getDuration(),
                    'date' => $entry->getDate()->format('Y-m-d'),
                ]
            ], Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/time-entries/delete/{id}', name: 'delete_time_entry', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $entry = $this->entityManager->getRepository(TimeEntries::class)->find($id);
            if (!$entry) {
                return $this->json(['status' => 'error', 'message' => 'Time entry not found'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($entry);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Time entry deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    //Edit time entries
    #[Route('/api/time-entries/{id}', name: 'app_time_entry_show', methods: ['GET'])]
    public function show(TimeEntries $timeEntry): JsonResponse
    {
        $task = $timeEntry->getTask();
        $user = $timeEntry->getUser();

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $timeEntry->getId(),
                'date' => $timeEntry->getDate()?->format('Y-m-d'),
                'duration' => $timeEntry->getDuration(),
                'comment' => $timeEntry->getComment(),
                'task' => $task ? [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus()?->value,
                    'priority' => $task->getPriority()?->value,
                    'project' => $task->getProjectId() ? [
                        'id' => $task->getProjectId()->getId(),
                        'name' => $task->getProjectId()->getName(),
                    ] : null,
                ] : null,
                'user' => $user ? [
                    'id' => $user->getId(),
                    'name' => $user->getFirstname() . ' ' . $user->getName(),
                    'email' => $user->getEmail(),
                ] : null,
            ],
        ]);
    }
}
