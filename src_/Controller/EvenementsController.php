<?php

namespace App\Controller;

use App\Entity\Departements;
use App\Entity\Evenements;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Helpers\Helpers;

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
    )
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->Helpers = $Helpers;
    }
    
    /**
    * @return Response
    **/
    #[Route('/evenement/list', name: 'app_evenements', methods: ['GET'])]
    public function departmentList(EntityManagerInterface $entityManager): Response
    {
        #$datas = $entityManager->getRepository(Evenements::class)->findAll(array("date_event" => "DESC"));
        $datas = $entityManager->getRepository(Evenements::class)->findBy([], ['date_event' => 'DESC', 'time_event' => 'DESC']);

        return $this->json($datas, 200, [], [
            'groups' => 'evenements'
        ]) ;
    }

    /**  
    * Enregistrement d'un Evènement
    * @param Request $request
    * @return JsonResponse
    */

    #[Route('/evenement/create', name: 'api_create_evenements', methods: ['POST'])]
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
            $requiredFields = ['name', 'location', 'departement_id', 'date_event', 'time_event'];

            // Validate required fields using the helper function
            $missingFields = $this->Helpers->validateRequiredFields($data, $requiredFields);
            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }

            // Récupérer le département
            $department = $this->entityManager->getRepository(Departements::class)->findOneBy(["name"=>$data["departement_id"]]);
            if (!$department) {
                throw new \InvalidArgumentException('Invalid department_id');
            }

            $dateEvent = \DateTime::createFromFormat('d/m/Y', $data['date_event']);
            if (!$dateEvent) {
                throw new \Exception("Invalid date format");
            }

            $timeEvent = \DateTime::createFromFormat('H:i', $data['time_event']);
            if (!$timeEvent) {
                throw new \Exception("Invalid time format");
            }

            $user = new Evenements();
            $user->setName($data["name"]);
            $user->setLocation($data["location"]);
            $user->setDepartement($department);
            $user->setDateEvent($dateEvent);
            $user->setTimeEvent($timeEvent);
            $user->setCreatedAt(new \DateTimeImmutable());
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
                'message' => 'Event created successfully'
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
