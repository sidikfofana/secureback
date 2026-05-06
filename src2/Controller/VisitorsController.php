<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Evenements;
use App\Entity\Visitors;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\VisitorsRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use App\Helpers\Helpers;
use App\Services\FileUploader;

class VisitorsController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;
    private $serializer;
    private $validator;
    private $Helpers;
    private $visitorsRepository;
    private $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        Helpers $Helpers,
        VisitorsRepository $visitorsRepository,
        UserRepository $userRepository,
        // private FileUploader $fileUploader
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->Helpers = $Helpers;
        $this->visitorsRepository = $visitorsRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @return Response
     **/
    #[Route('/visitors/list', name: 'app_visitors', methods: ['GET'])]
    public function visitorsList(EntityManagerInterface $entityManager): Response
    {
        $datas = $entityManager->getRepository(Visitors::class)->findAll(array("created_at" => "DESC"));
        return $this->json($datas, 200, [], [
            'groups' => 'visitor'
        ]);
    }

    /**  
     * Enregistrement d'un visiteur
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/visitor/create', name: 'create_visitor', methods: ['POST'])]
    public function createVisitor(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            // Define required fields
            $requiredFields = ['firstname', 'lastname', 'email', 'contact', 'address'];

            // Validate required fields using the helper function
            $missingFields = $this->Helpers->validateRequiredFields($data, $requiredFields);
            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }

            if (isset($data['user_id'])) {
                $user = $this->userRepository->find($data['user_id']);

                if (!$user) {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'User not found'
                    ], Response::HTTP_NOT_FOUND);
                }
            }

            $visitor = new Visitors();
            if (isset($data["evenements_id"])) {
                $event = $this->entityManager->getRepository(Evenements::class)->find($data['evenements_id']);
                $visitor->setEvenements($event);
            }

            if (isset($data["company_id"])) {
                $company = $this->entityManager->getRepository(Company::class)->find($data['company_id']);
                $visitor->setCompany($company);
            }

            $visitor->setUser($user ?? null);
            $visitor->setFirstname($data['firstname']);
            $visitor->setLastname($data['lastname']);
            $visitor->setEmail($data['email']);
            $visitor->setContact($data['contact']);
            $visitor->setAddress($data['address']);
            $visitor->setOrganisationName(  $data['organisation_name']);
            $visitor->setVisitorType((int) $data['visitor_type']);
            $visitor->setIdNumber($data['id_number']);
            $visitor->setCreatedAt(new \DateTimeImmutable());
            $visitor->setUpdatedAt(new \DateTimeImmutable());


            // Save the visitor entity
            $this->entityManager->persist($visitor);
            $this->entityManager->flush();

            // Validate the user entity
            $errors = $this->validator->validate($visitor);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;

                return $this->json([
                    'status' => 'error',
                    'message' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Visitor created successfully',
                'data' => [
                    "contact" => $visitor->getContact(),
                    "visitor_id" => $visitor->getId(),
                ]
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
