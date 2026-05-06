<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Company;
use App\Helpers\Helpers;
use App\Entity\UserCheckIn;
use App\Entity\Departements;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;
    private $serializer;
    private $validator;
    private $Helpers;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        Helpers $Helpers,
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->Helpers = $Helpers;
    }

    /**
     * @return Response
     **/
    #[Route('/api/user/list', name: 'app_user', methods: ['GET'])]
    public function userList(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        // dd($userId);
        $userId = $user->getId();
        $queryBuilder = $entityManager->getRepository(User::class)->createQueryBuilder('u');
        $queryBuilder->where('u.id != :userId')
             ->orderBy('u.create_at', 'DESC')
             ->setParameter('userId', $userId);
        $datas = $queryBuilder->getQuery()->getResult();
        return $this->json($datas, 200, [], [
            'groups' => 'users'
        ]);
    }
    
    /**
     * @return Response
     **/
    #[Route('/api/user/list/{companySlug}', name: 'app_user_by_comp', methods: ['GET'])]
    public function userListComp(EntityManagerInterface $entityManager, $companySlug): Response
    {
        $user = $this->getUser();
        //dd($user);
        $userId = $user->getId();
        //dd($userId);
        $company = $entityManager->getRepository(Company::class)
            ->findOneBy(['slug' => $companySlug]);

        if (empty($company)) {
            return $this->json([
                "error" => 'not found company',
            ], 404);
        }

        $queryBuilder = $entityManager->getRepository(User::class)->createQueryBuilder('u');
        $queryBuilder->where('u.company = :company')
             ->andWhere('u.id != :userId')
             ->setParameter('company', $company)
             ->setParameter('userId', $userId)
             ->orderBy('u.create_at', 'DESC');
        $datas = $queryBuilder->getQuery()->getResult();
        return $this->json($datas, 200, [], [
            'groups' => 'users'
        ]);
    }

    //récupérer les utlisateurs employees d'une entreprise
    #[Route('/api/user/{companySlug}/employees', name: 'api_users_by_company_employee', methods: ['GET'])]
    public function userListEmployeesByCompany(
        EntityManagerInterface $entityManager,
        string $companySlug
    ): Response {
        $currentUser = $this->getUser();

        //Récupérer la compagnie
        $company = $entityManager->getRepository(Company::class)
            ->findOneBy(['slug' => $companySlug]);

        if (!$company) {
            return $this->json([
                "error" => 'Company not found',
            ], 404);
        }

        // Récupérer tous les utilisateurs de la compagnie
        $users = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.company = :company')
            ->setParameter('company', $company)
            ->orderBy('u.name', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();

        // Filtrer les utilisateurs ayant le rôle ROLE_EMPLOYEE
        $employees = array_filter($users, function ($user) {
            return in_array('ROLE_EMPLOYEE', $user->getRoles(), true);
        });

        // Retourner la liste complète (y compris l’utilisateur connecté s’il correspond)
        return $this->json(array_values($employees), 200, [], [
            'groups' => 'users',
        ]);
    }


    /**  
     * Enregistrement d'un utilisateur
     * @param Request $request
     * @return JsonResponse
     */

    #[Route('/api/user/create', name: 'api_create_user', methods: ['POST'])]
    public function createUser(Request $request): Response
    {
        //dd($request);
        try {

            $data = json_decode($request->getContent(), true);
            //dd($data);
            if ($data === null) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            // Define required fields
            $requiredFields = ['name', 'firstname', 'email', 'password', 'role', 'title', 'company_id'];

            // Validate required fields using the helper function
            $missingFields = $this->Helpers->validateRequiredFields($data, $requiredFields);
            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }
            $departmentId =1;
            // Récupérer le département
            $department = $this->entityManager
                ->getRepository(Departements::class)
                ->find($departmentId);
            // ->findOneBy(["name"=>$data["department_id"]]);
            if (!$department) {
                throw new \InvalidArgumentException('Invalid department_id');
            }

            // Récupérer le département
            $company = $this->entityManager
                ->getRepository(Company::class)
                ->find($data["company_id"]);
            // ->findOneBy(["name"=>$data["company_id"]]);
            if (!$company) {
                throw new \InvalidArgumentException('Invalid company_id');
            }

            $user = new User();
            $user->setName($data["name"]);
            $user->setFirstname($data["firstname"]);
            $user->setEmail($data['email']);
            $user->setContact($data['contact']);
            $user->setTitle($data['title']);
            $user->setRole([$data['role']] ?? ['ROLE_USER']);
            $user->setDepartment($department);
            $user->setCompany($company);
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $data['password'] ?? ''
                )
            );
            $user->setCreateAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());
            $user->setStatus(1);

            // Validate the user entity
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;

                return $this->json([
                    'status' => 'error',
                    'message' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'User created successfully'
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            // Log the exception message if needed
            // $this->logger->error($e->getMessage());
            //return new Response('An unexpected error occurred', Response::HTTP_INTERNAL_SERVER_ERROR);
            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/userCheckInList', name: 'user_checkin_list')]
    public function getUserCheckInlist(EntityManagerInterface $entityManager): JsonResponse
    {
		$datas = $this->entityManager->getRepository(UserCheckIn::class)->findBy([], ['created_at' => 'DESC']);
        $data = [];
        foreach ($datas as $userCheckIn) {
            // dd($userCheckIn);
            $data[] = [
                //'id' => $checkIn->getId(),
                'user_id' => $userCheckIn->getQrUser()->getId(),
                'user_uidn' => $userCheckIn->getQrUser()->getUidn(),
                'user_email' => $userCheckIn->getQrUser()->getEmail(),
                'user_firstname' => $userCheckIn->getQrUser()->getFirstname(),
                'user_name' => $userCheckIn->getQrUser()->getLastName(),
                'user_phone' => $userCheckIn->getQrUser()->getContact(),
                'user_title' => $userCheckIn->getQrUser()->getTitle(),
                'type_qr' => $userCheckIn->getQrUser()->getType(),
                'image' =>  $userCheckIn->getQrUser()->getUserImage(),
                'codeqr' =>  $userCheckIn->getQrUser()->getCode(),
                'company_id' => $userCheckIn->getQrUser()->getCompany()?->getId(),
                'check_logs' => $userCheckIn->getCheckLog() ?? [],
            ];
        }
        return new JsonResponse($data);
    }
    #[Route('/api/user/{id}', name: 'api_delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }
        $entityManager->remove($user);
        $entityManager->flush();
        return $this->json(['message' => 'User deleted successfully'], 200);
    }

    #[Route('/api/user/{id}/change-password', name: 'api_user_change_password', methods: ['PUT'])]
    public function changePassword(
        int $id,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        Security $security
    ): JsonResponse {
        $currentUser = $security->getUser();

        // Sécurité : empêcher de modifier un autre utilisateur si non admin
        if ($currentUser->getId() !== $id) {
            return new JsonResponse(['message' => 'Accès non autorisé.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        $user = $userRepository->find($id);

        if (!$user || !$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return new JsonResponse(['message' => 'Mot de passe actuel incorrect.'], 400);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $em->flush();

        return new JsonResponse(['message' => 'Mot de passe mis à jour avec succès.']);
    }

    // Afficher un utilisateur
    #[Route('/api/users/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'firstname' => $user->getFirstname(),
                'email' => $user->getEmail(),
                'title' => $user->getTitle(),
                'contact' => $user->getContact(),
                'roles' => $user->getRoles(),
                'status' => $user->isStatus(),
                'department' => $user->getDepartment()?->getName(),
                'company' => $user->getCompany()?->getName(),
                'companyId' => $user->getCompany()?->getId(),
                'created_at' => $user->getCreateAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    //Mise à jour
    #[Route('/api/user/update/{id}', name: 'update_user', methods: ['POST'])]
    public function updateUser(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $userToUpdate = $em->getRepository(User::class)->find($id);

            if (!$userToUpdate) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier utilisateur connecté
            /** @var UserInterface|null $currentUser */
            $currentUser = $this->getUser();
            if (!$currentUser) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Vérifier permission : soit c’est l’utilisateur lui-même, soit admin/super admin
            $currentRoles = $currentUser->getRoles();
            if ($currentUser->getId() !== $userToUpdate->getId() &&
                !in_array('ROLE_ADMIN', $currentRoles, true) &&
                !in_array('ROLE_SUPER_ADMIN', $currentRoles, true)
            ) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'You are not allowed to update this user'
                ], Response::HTTP_FORBIDDEN);
            }

            // Récupérer données JSON
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            //dd($data);

            // Mise à jour des champs
            if (isset($data['name'])) $userToUpdate->setName($data['name']);
            if (isset($data['firstname'])) $userToUpdate->setFirstname($data['firstname']);
            if (isset($data['email'])) $userToUpdate->setEmail($data['email']);
            if (isset($data['title'])) $userToUpdate->setTitle($data['title']);
            if (isset($data['contact'])) $userToUpdate->setContact($data['contact']);
            if (isset($data['roles']) && is_array($data['roles'])) $userToUpdate->setRole($data['roles']);
            if (isset($data['companyId'])) $userToUpdate->setCompanyId($data['companyId']);
            if (isset($data['status'])) $userToUpdate->setStatus((bool)$data['status']);
            // Optionnel : mot de passe seulement si fourni
            if (isset($data['password']) && $data['password'] !== '') {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $userToUpdate->setPassword($hashedPassword);
            }

            $userToUpdate->setUpdatedAt(new \DateTimeImmutable());

            // Validation Symfony
            $errors = $validator->validate($userToUpdate);
            if (count($errors) > 0) {
                return $this->json([
                    'status' => 'error',
                    'message' => (string)$errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $em->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $userToUpdate->getId(),
                    'name' => $userToUpdate->getName(),
                    'firstname' => $userToUpdate->getFirstname(),
                    'email' => $userToUpdate->getEmail(),
                    'roles' => $userToUpdate->getRoles(),
                    'companyId' => $userToUpdate->getCompany()?->getId(),
                    'companyName' => $userToUpdate->getCompany()?->getName(),
                    'status' => $userToUpdate->isStatus(),
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
}
