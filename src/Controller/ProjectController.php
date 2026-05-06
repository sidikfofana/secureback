<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Company;
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
use App\Repository\CompanyRepository;
use App\Enum\ProjectStatus;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class ProjectController extends AbstractController
{
    public function __construct(
        private Helpers $Helpers,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private FileUploader $fileUploader,
        private userRepository $userRepository,
        private CompanyRepository $companyRepository,
        private EntityManagerInterface $entityManager, 
        private ValidatorInterface $validator,
         private TokenStorageInterface $tokenStorage,
    ) {
    } 

    #[Route('/api/projects/list', name: 'app_project', methods: ['GET'])]
    /*public function index(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $projects = $entityManager->getRepository(Project::class)->findBy([], ['created_at' => 'DESC']);

        return $this->json($projects, 200, [], ['groups' => 'projects']);
    }*/

    public function index(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Récupérer l'utilisateur connecté
            $user = $this->getUser();
            if (!$user || !is_object($user)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $criteria = [];

            // Vérifier le rôle de l'utilisateur
            if (
                !in_array('ROLE_ADMIN', $user->getRoles(), true) &&
                !in_array('ROLE_SUPERVISOR', $user->getRoles(), true)
            ) {
                // Si l'utilisateur n'est pas admin ou superadmin, filtrer par sa compagnie
                $company = $user->getCompany();

                if (!$company) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Aucune compagnie associée à cet utilisateur'
                    ], Response::HTTP_FORBIDDEN);
                }

                $criteria['company'] = $company;
            } else {
                // Si c’est un admin ou superadmin, il peut éventuellement filtrer via ?company_id=
                $companyId = $request->query->get('company_id');
                if ($companyId) {
                    $company = $entityManager->getRepository(Company::class)->find($companyId);
                    if (!$company) {
                        return $this->json([
                            'status' => 'error',
                            'message' => 'Company not found'
                        ], Response::HTTP_NOT_FOUND);
                    }

                    $criteria['company'] = $company;
                }
            }

            // Récupération des projets selon les critères
            $projects = $entityManager->getRepository(Project::class)
                ->findBy($criteria, ['created_at' => 'DESC']);

            return $this->json([
                'status' => 'success',
                'count' => count($projects),
                'data' => $projects
            ], Response::HTTP_OK, [], ['groups' => 'projects']);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/projects/create', name: 'create_project', methods: ['POST'])]
    public function createProject(Request $request): JsonResponse
    {
        try {
            $data = $request->request->all();
            $file = $request->files->get('srs_file');

            // Champs obligatoires
            $requiredFields = ['company_id', 'name', 'description', 'start_date', 'end_date', 'status', 'progress'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }

            // Vérifier la compagnie
            $company = $this->companyRepository->find($data['company_id']);
            if (!$company) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Company not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Récupération de l'utilisateur connecté
            $user = $this->tokenStorage->getToken()?->getUser();
            if (!$user || !is_object($user)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Créer le projet
            $project = new Project();
            $project->setCompany($company);
            $project->setName($data['name']);
            $project->setDescription($data['description']);
            $project->setStartDate(new \DateTime($data['start_date']));
            $project->setEndDate(new \DateTime($data['end_date']));
            $project->setStatus(ProjectStatus::from($data['status'])); // si enum PHP 8.1
            $project->setProgress((int)$data['progress']);
            $project->setCreatedBy($user);
            $project->setCreatedAt(new \DateTimeImmutable());
            $project->setUpdatedAt(new \DateTimeImmutable());

            // Upload du fichier (optionnel)
            if ($file) {
                $uploadedFilePath = $this->fileUploader->upload($file, "srs_files");
                $project->setSrsFile($uploadedFilePath);
            }

            $this->entityManager->persist($project);
            $this->entityManager->flush();

            // Validation de l’entité
            $errors = $this->validator->validate($project);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                return $this->json([
                    'status' => 'error',
                    'message' => $errorsString,
                ], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Project created successfully',
                'data' => [
                    'id' => $project->getId(),
                    'name' => $project->getName(),
                    'company' => $company->getName(),
                    'status' => $project->getStatus()->value,
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

    #[Route('/api/projects/update/{id}', name: 'update_project', methods: ['POST'])]
    public function updateProject(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CompanyRepository $companyRepository,
        ValidatorInterface $validator,
        FileUploader $fileUploader
    ): JsonResponse {
        try {
            // 🔹 Récupérer le projet
            $project = $em->getRepository(Project::class)->find($id);
            if (!$project) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Project not found.'
                ], Response::HTTP_NOT_FOUND);
            }

            // 🔹 Récupérer les données du formulaire
            $data = $request->request->all();
            $file = $request->files->get('srs_file');

            // 🔹 Vérification de la compagnie
            if (!empty($data['company_id'])) {
                $company = $companyRepository->find($data['company_id']);
                if (!$company) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Company not found.'
                    ], Response::HTTP_NOT_FOUND);
                }
                $project->setCompany($company);
            }

            // 🔹 Mise à jour des champs texte
            if (!empty($data['name'])) {
                $project->setName($data['name']);
            }
            if (!empty($data['description'])) {
                $project->setDescription($data['description']);
            }
            if (!empty($data['start_date'])) {
                $project->setStartDate(new \DateTime($data['start_date']));
            }
            if (!empty($data['end_date'])) {
                $project->setEndDate(new \DateTime($data['end_date']));
            }
            if (isset($data['progress'])) {
                $project->setProgress((int)$data['progress']);
            }
            if (!empty($data['status'])) {
                $project->setStatus(ProjectStatus::fromInt($data['status']));
            }

            // 🔹 Gestion du fichier uploadé
            if ($file) {
                $uploadedFilePath = $fileUploader->upload($file, "srs_files");
                $project->setSrsFile($uploadedFilePath);
            }

            $project->setUpdatedAt(new \DateTimeImmutable());

            // 🔹 Validation de l’entité
            $errors = $validator->validate($project);
            if (count($errors) > 0) {
                return $this->json([
                    'status' => 'error',
                    'message' => (string)$errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $em->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Project updated successfully.',
                'data' => [
                    'id' => $project->getId(),
                    'name' => $project->getName(),
                    'company' => $project->getCompany()->getName(),
                    'status' => $project->getStatus()->value,
                    'progress' => $project->getProgress(),
                    'updated_at' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Error updating project: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //Supprimer le projet
    #[Route('/api/projects/delete/{id}', name: 'delete_project', methods: ['DELETE'])]
    public function deleteProject(int $id): JsonResponse
    {
        try {
            $project = $this->entityManager->getRepository(Project::class)->find($id);

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
                'message' => 'Project deleted successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //Editer le projet
    #[Route('/api/projects/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(Project $project): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $project->getId(),
                'title' => $project->getName(),
                'start_date' => $project->getStartDate()?->format('Y-m-d'),
                'end_date' => $project->getEndDate()?->format('Y-m-d'),
                'description' => $project->getDescription(),
                'company_id' => $project->getCompany()->getId(),
                'company' => $project->getCompany()->getName(),
                'status' => $project->getStatus()->value,
                'progress' => $project->getProgress(),
                'document_file' => $project->getSrsFile(),
            ],
        ]);
    }
}
