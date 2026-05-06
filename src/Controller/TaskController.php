<?php

namespace App\Controller;

use App\Entity\Tasks;
use App\Entity\Project;
use App\Entity\User;
use App\Helpers\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Services\FileUploader;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskController extends AbstractController
{
    public function __construct(
        private Helpers $Helpers,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private FileUploader $fileUploader,
        private userRepository $userRepository,
        private EntityManagerInterface $entityManager, 
        private ValidatorInterface $validator,
    ) {
    }

    // #[Route('/api/task/list', name: 'app_task', methods: ['GET'])]
    // public function index(Request $request, EntityManagerInterface $entityManager): JsonResponse
    // {
    //     $projects = $entityManager->getRepository(Tasks::class)->findBy([], ['created_at' => 'DESC']);

    //     return $this->json($projects, 200, [], ['groups' => 'tasks']);
    // }

    #[Route('/api/tasks/list', name: 'app_task', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $projectId = $request->query->get('project_id');
            $criteria = [];

            if ($projectId) {
                $project = $entityManager->getRepository(Project::class)->find($projectId);
                if (!$project) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Project not found'
                    ], Response::HTTP_NOT_FOUND);
                }

                $criteria['project_id'] = $project;
            }

            $tasks = $entityManager->getRepository(Tasks::class)
                ->findBy($criteria, ['created_at' => 'DESC']);

            return $this->json([
                'status' => 'success',
                'count' => count($tasks),
                'data' => $tasks
            ], Response::HTTP_OK, [], ['groups' => 'tasks']);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/api/tasks/create', name: 'create_task', methods: ['POST'])]
    public function createTask(Request $request, EntityManagerInterface $em, ProjectRepository $projectRepo, UserRepository $userRepo): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            // Champs obligatoires
            $requiredFields = ['title', 'project_id'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }

            // Vérifier le projet
            $project = $projectRepo->find($data['project_id']);
            if (!$project) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Project not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier l'utilisateur assigné si fourni
            $assignedTo = null;
            if (!empty($data['assigned_to_id'])) {
                $assignedTo = $userRepo->find($data['assigned_to_id']);
                if (!$assignedTo) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Assigned user not found'
                    ], Response::HTTP_NOT_FOUND);
                }
            }

            // Utilisateur connecté qui crée la tâche
            $createdBy = $this->getUser();
            if (!$createdBy) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Convertir enums Priority et Status
            $priority = null;
            if (!empty($data['priority'])) {
                try {
                    $priority = TaskPriority::from($data['priority']);
                } catch (\ValueError $e) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Invalid priority value'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $status = null;
            if (!empty($data['status'])) {
                try {
                    $status = TaskStatus::from($data['status']);
                } catch (\ValueError $e) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Invalid status value'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Créer la tâche
            $task = new Tasks();
            $task->setProjectId($project);
            $task->setTitle($data['title']);
            $task->setDescription($data['description'] ?? null);
            $task->setCreatedBy($createdBy); // <-- assignation du créateur
            if ($assignedTo) $task->setAssignedTo($assignedTo);
            if ($priority) $task->setPriority($priority);
            if (!empty($data['start_date'])) $task->setStartDate(new \DateTimeImmutable($data['start_date']));
            if (!empty($data['due_date'])) $task->setDueDate(new \DateTimeImmutable($data['due_date']));
            if (isset($data['estimated_time'])) $task->setEstimatedTime($data['estimated_time']);
            if (isset($data['spent_time'])) $task->setSpentTime((string)$data['spent_time']);
            if ($status) $task->setStatus($status);

            // Tâche parente si fournie
            if (!empty($data['parent_task_id'])) {
                $parent = $em->getRepository(Tasks::class)->find($data['parent_task_id']);
                if ($parent) $task->setParentTaskId($parent);
            }

            $task->setCreatedAt(new \DateTimeImmutable());
            $task->setUpdatedAt(new \DateTimeImmutable());

            $em->persist($task);
            $em->flush();

            // Validation Symfony
            $errors = $this->validator->validate($task);
            if (count($errors) > 0) {
                return $this->json([
                    'status' => 'error',
                    'message' => (string)$errors
                ], Response::HTTP_BAD_REQUEST);
            }

            return $this->json([
                'status' => 'success',
                'message' => 'Task created successfully',
                'data' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus()?->value,
                    'priority' => $task->getPriority()?->value,
                    'created_by' => $task->getCreatedBy()->getId(),
                ]
            ], Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/tasks/update/{id}', name: 'update_task', methods: ['PUT'])]
    public function updateTask(int $id, Request $request, EntityManagerInterface $em, UserRepository $userRepo): JsonResponse
    {
        try {
            $task = $em->getRepository(Tasks::class)->find($id);
            if (!$task) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Task not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier que l'utilisateur connecté est le créateur
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Optionnel : vérifier si admin ou rôle spécifique
            if ($task->getCreatedBy()->getId() !== $user->getId()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'You are not allowed to update this task'
                ], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            // Mise à jour des champs simples
            if (isset($data['title'])) $task->setTitle($data['title']);
            if (isset($data['description'])) $task->setDescription($data['description']);
            if (isset($data['start_date'])) $task->setStartDate(new \DateTimeImmutable($data['start_date']));
            if (isset($data['due_date'])) $task->setDueDate(new \DateTimeImmutable($data['due_date']));
            if (isset($data['estimated_time'])) $task->setEstimatedTime($data['estimated_time']);
            if (isset($data['spent_time'])) $task->setSpentTime((string)$data['spent_time']);

            // Mise à jour de l'utilisateur assigné
            if (isset($data['assigned_to_id'])) {
                $assignedTo = $userRepo->find($data['assigned_to_id']);
                if (!$assignedTo) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Assigned user not found'
                    ], Response::HTTP_NOT_FOUND);
                }
                $task->setAssignedTo($assignedTo);
            }

            // Mise à jour de la priorité (enum)
            if (isset($data['priority'])) {
                try {
                    $task->setPriority(TaskPriority::fromInt($data['priority']));
                } catch (\ValueError $e) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Invalid priority value'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Mise à jour du status (enum)
            if (isset($data['status'])) {
                try {
                    $task->setStatus(TaskStatus::fromInt($data['status']));
                } catch (\ValueError $e) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Invalid status value'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Mise à jour de la tâche parente
            if (isset($data['parent_task_id'])) {
                $parent = $em->getRepository(Tasks::class)->find($data['parent_task_id']);
                if ($parent) {
                    $task->setParentTask($parent);
                }
            }

            $task->setUpdatedAt(new \DateTimeImmutable());

            // Validation Symfony
            $errors = $this->validator->validate($task);
            if (count($errors) > 0) {
                return $this->json([
                    'status' => 'error',
                    'message' => (string)$errors,
                ], Response::HTTP_BAD_REQUEST);
            }

            $em->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Task updated successfully',
                'data' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus()?->value,
                    'priority' => $task->getPriority()?->value,
                    'assigned_to' => $task->getAssignedTo()?->getId(),
                    'created_by' => $task->getCreatedBy()?->getId(),
                ]
            ], Response::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/api/tasks/delete/{id}', name: 'delete_task', methods: ['DELETE'])]
    public function deleteTask(int $id): JsonResponse
    {
        try {
            $project = $this->entityManager->getRepository(Tasks::class)->find($id);

            if (!$project) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Project not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($project);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Task deleted successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //Editer une tâche
    #[Route('/api/tasks/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(Tasks $task): JsonResponse
    {
        $project = $task->getProjectId();
        $assignedTo = $task->getAssignedTo();
        $createdBy = $task->getCreatedBy();
        $parentTask = $task->getParentTaskId();

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'priority' => $task->getPriority()?->value,
                'status' => $task->getStatus()?->value,
                'start_date' => $task->getStartDate()?->format('Y-m-d'),
                'due_date' => $task->getDueDate()?->format('Y-m-d'),
                'estimated_time' => $task->getEstimatedTime(),
                'spent_time' => $task->getSpentTime(),
                'project' => $project ? [
                    'id' => $project->getId(),
                    'title' => $project->getName(),
                    'company' => $project->getCompany()->getName(),
                ] : null,
                'assigned_to' => $assignedTo ? [
                    'id' => $assignedTo->getId(),
                    'name' => $assignedTo->getFirstname() . ' ' . $assignedTo->getName(),
                    'email' => $assignedTo->getEmail(),
                ] : null,
                'created_by' => $createdBy ? [
                    'id' => $createdBy->getId(),
                    'name' => $createdBy->getFirstname() . ' ' . $createdBy->getFirstname(),
                ] : null,
                'parent_task' => $parentTask ? [
                    'id' => $parentTask->getId(),
                    'title' => $parentTask->getTitle(),
                ] : null,
                'created_at' => $task->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $task->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
