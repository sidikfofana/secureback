<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Departements;
use App\Entity\Evenements;
use App\Repository\EvenementsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Helpers\Helpers;
use App\Repository\CompanyRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class EvenementsController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;
    private $serializer;
    private $validator;
    private $Helpers;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        Helpers $Helpers,
        private CompanyRepository $companyRepository,
        private EvenementsRepository $eventRepo,
        private SluggerInterface $slugger,
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->Helpers = $Helpers;
    }

    /**
     * @return Response
     **/
    #[Route('/api/evenement/list', name: 'app_evenements', methods: ['GET'])]
    public function departmentList(EntityManagerInterface $entityManager): Response
    {
        #$datas = $entityManager->getRepository(Evenements::class)->findAll(array("date_event" => "DESC"));
        $datas = $entityManager->getRepository(Evenements::class)->findBy([], ['date_event' => 'DESC', 'time_event' => 'DESC']);

        return $this->json($datas, 200, [], [
            'groups' => 'evenements'
        ]);
    }

    /**
     * @return Response
     **/
    #[Route('/api/evenement/list/{companySlug}', name: 'app_evenements_by_company', methods: ['GET'])]
    public function departmentListByCompany(EntityManagerInterface $entityManager, $companySlug): Response
    {
        $company = $entityManager->getRepository(Company::class)->findOneBy(['slug' => $companySlug]);

        if (empty($company)) {
            return $this->json([
                "error" => 'not found company',
            ], 404);
        }

        $datas = $entityManager->getRepository(Evenements::class)->findBy(
            ["company" => $company],
            ['date_event' => 'DESC', 'time_event' => 'DESC']
        );

        return $this->json($datas, 200, [], [
            'groups' => 'evenements'
        ]);
    }

    /**  
     * Enregistrement d'un Evènement
     * @param Request $request
     * @return JsonResponse
     */

    #[Route('/api/evenement/create', name: 'api_create_evenements', methods: ['POST'])]
    public function createUser(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            // Define required fields
            $requiredFields = [
                'name',
                'company_id',
                'location',
                // 'departement_id', 
                'date_event',
                'time_event'
            ];

            // Validate required fields using the helper function
            $missingFields = $this->Helpers->validateRequiredFields($data, $requiredFields);
            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }

            // Récupérer le département
            $department = $this->entityManager->getRepository(Departements::class)->find($data["departement_id"] ?? 1);
            if (!$department) {
                throw new \InvalidArgumentException('Invalid department_id');
            }

            $dateEvent = \DateTime::createFromFormat('d/m/Y', $data['date_event']);
            $dateEvent = new \DateTime($data['date_event']);
            if (!$dateEvent) {
                throw new \Exception("Invalid date format");
            }

            $timeEvent = \DateTime::createFromFormat('H:i', $data['time_event']);
            if (!$timeEvent) {
                throw new \Exception("Invalid time format");
            }

            $company = $this->companyRepository->find($data['company_id']);
            if (empty($company)) {
                return $this->json([
                    "message" => "Entreprise introuvable"
                ], 404);
            }

            $event = new Evenements();
            $event->setName($data["name"])->generateSlug($this->slugger);
            $event->setCompany($company);
            $event->setLocation($data["location"]);
            $event->setAddressName($data["address_name"]);
            $event->setDepartement($department);
            $event->setDateEvent($dateEvent);
            $event->setTimeEvent($timeEvent);
            $event->setCreatedAt(new \DateTimeImmutable());
            $event->setUpdatedAt(new \DateTimeImmutable());
            $event->setStatus(status: 1);

            // Validate the user entity
            $errors = $this->validator->validate($event);

            if (count($errors) > 0) {
                $errorsString = (string) $errors;

                return $this->json([
                    'status' => 'error',
                    'message' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $lien = $this->getParameter('domain_front') . "/company/" . $event->getCompany()->getSlug() . '/event/' . $event->getSlug();
            $this->Helpers->generateEncryptLink($lien, $event->getSlug());

            return $this->json(
                [
                    'status' => 'success',
                    'data' => [
                        "lien" => $lien,
                        "event" => $event
                    ],
                    'message' => 'Event created successfully'
                ],
                Response::HTTP_CREATED,
                [],
                [
                    "groups" => "evenements"
                ]
            );
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
    /**  
     * Enregistrement d'un Evènement
     * @param Request $request
     * @return JsonResponse
     */

    #[Route('/api/evenement/{slug}', name: 'api_get_evenements', methods: ['GET'])]
    public function show($slug): Response
    {
        $event = $this->eventRepo->findOneBy(["slug" => $slug]);

        if (empty($event)) {
            return $this->json(
                [
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message' => "Evenement Non Rétrouvé"
                ],
                status: Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(
            data: [
                "data" => $event,
                'status' => Response::HTTP_OK,
                'message' => "Evenement Rétrouvé"
            ],
            status: Response::HTTP_OK,
            headers: [],
            context: ['groups' => "evenements"]
        );
    }
}
