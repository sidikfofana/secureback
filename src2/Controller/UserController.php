<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Departements;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use App\Helpers\Helpers;

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
        $datas = $entityManager->getRepository(User::class)->findAll(array("create_at" => "DESC"));
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

        $company = $entityManager->getRepository(Company::class)
            ->findOneBy(['slug' => $companySlug]);

        if (empty($company)) {
            return $this->json([
                "error" => 'not found company',
            ], 404);
        }

        $datas = $entityManager->getRepository(User::class)->findBy(['company' => $company], array("create_at" => "DESC"));
        return $this->json($datas, 200, [], [
            'groups' => 'users'
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
            $requiredFields = ['name', 'firstname', 'email', 'password', 'role', 'title', 'department_id', 'company_id'];

            // Validate required fields using the helper function
            $missingFields = $this->Helpers->validateRequiredFields($data, $requiredFields);
            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }

            // Récupérer le département
            $department = $this->entityManager
                ->getRepository(Departements::class)
                ->find($data["department_id"]);
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
}
