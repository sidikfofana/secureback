<?php

namespace App\Controller;
use App\Entity\Company;
use App\Entity\Evenements;
use App\Entity\Visitors;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\VisitorsRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Helpers\Helpers;
use App\Services\FileUploader;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventAttendanceController extends AbstractController
{
	private $entityManager;
    private $passwordHasher;
    private $serializer;
    private $validator;
    private $Helpers;
    private $visitorsRepository;
    private $userRepository;
	private $evenements;

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
	
    #[Route('/api/eventattendencelist', name: 'app_event_attendance')]
    public function geteventattendencelist(EntityManagerInterface $entityManager): JsonResponse
    {

        $datas = $entityManager->getRepository(Evenements::class)->findBy([], ['date_event' => 'DESC', 'time_event' => 'DESC']);
        foreach ($datas as $event) {
            $event_id = (string) $event->getId();
            $visitors = $entityManager->getRepository(Visitors::class)->findBy(['evenements' => $event_id]);
            if ($visitors) {
                $responseData[] = [
                    'event' => $event,
                    'visitors' => $visitors,
                ];
            }
            
        }
        // Return the response as a JSON
        return $this->json($responseData, 200, [], [
            'groups' => ['visitor', 'evenements'], // Include both groups
        ]);      
    }

    #[Route('/api/event/visitorLog/{companySlug}', name: 'visitor_evenements_by_company', methods: ['GET'])]
    public function visitorListByCompanyEvent(EntityManagerInterface $entityManager, $companySlug): Response
    {
        $company = $entityManager->getRepository(Company::class)->findOneBy(['slug' => $companySlug]);

        if (!$company) {
            return $this->json([
                "error" => 'Company not found',
            ], 404);
        }

        $datas = $entityManager->getRepository(Evenements::class)->findBy(['company' => $company],  [ 'time_event' => 'DESC']);
        $responseData = [];

        foreach ($datas as $event) {
            $event_id = (string) $event->getId();
            $visitors = $entityManager->getRepository(Visitors::class)->findBy(['evenements' => $event_id]);

            if ($visitors) {
                $responseData[] = [
                    'visitorCount' => count($visitors),
                    'event' => $event, // Ensure event object is properly serialized if needed
                    'visitors' => $visitors,
                ];
            }
        }
        // Return the response as a JSON
        return $this->json($responseData, 200, [], [
            'groups' => ['visitors','visitor', 'evenements'], // Include both groups
        ]);
    }


}
