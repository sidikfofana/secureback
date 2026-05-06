<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Company;
use App\Entity\CompanyType;
use App\Repository\CompanyRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Helpers\Helpers;
use App\Entity\User;
use App\Entity\Visitors;
use App\Entity\QRUser; 
use App\Entity\Evenements;
use App\Entity\CheckIns;
use App\Entity\Requests;
use App\Entity\QRCodes;
use App\Entity\Members;

class CompanyListController extends AbstractController
{
	private $entityManager;
    private $validator;
    private $resourceName = 'Entreprise';

   public function __construct(
        EntityManagerInterface $entityManager, 
        SerializerInterface $serializer, 
        ValidatorInterface $validator,
        Helpers $Helpers
    )
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->Helpers = $Helpers;
    }
	
    /*#[Route('/api/companylist', name: 'app_company_list')]
    public function getcompanylist(EntityManagerInterface $entityManager): JsonResponse
    {
		//$companies = $this->$entityManager->getRepository(Company::class)->findBy([], ['createdAt' => 'DESC']);
		$data = $this->entityManager->getRepository(Company::class)->findBy([], ['createdAt' => 'DESC']);
      return $this->json(
            [
                'data' => $data
            ],
            Response::HTTP_OK,
            [],
            ["groups" => 'company']
        );
	

    return new JsonResponse($data);
    }*/

    #[Route('/api/companylist', name: 'app_company_list')]
    public function getcompanylist(): JsonResponse
    {
        $companies = $this->entityManager->getRepository(Company::class)->findBy([], ['createdAt' => 'DESC']);

        $normalizedCompanies = [];

        foreach ($companies as $company) {
            $memberCount = $this->entityManager->getRepository(Members::class)
                ->count(['company' => $company]);

            // Serialize the company using the 'company' group
            $companyData = $this->serializer->normalize($company, null, ['groups' => ['company']]);

            // Add member_count manually
            $companyData['member_count'] = $memberCount;

            $normalizedCompanies[] = $companyData;
        }

        return new JsonResponse(
            ['data' => $normalizedCompanies],
            Response::HTTP_OK
        );
    }



    #[Route('/api/company/delete/{id}', name: 'delete_company', methods: ['DELETE'])]
    public function deleteCompany(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->getConnection()->beginTransaction(); 

        try {
            $company = $entityManager->getRepository(Company::class)->find($id);

            if (!$company) {
                return $this->json(['message' => 'Company not found'], 404);
            }

            // Get all related entities
            $users = $entityManager->getRepository(User::class)->findBy(['company' => $company]);
            $visitors = $entityManager->getRepository(Visitors::class)->findBy(['company' => $company]);
            $qrUsers = $entityManager->getRepository(Qruser::class)->findBy(['company' => $company]);
            $events = $entityManager->getRepository(Evenements::class)->findBy(['company' => $company]);

            // First delete all dependent records for visitors
            foreach ($visitors as $visitor) {
                // Delete check-ins for this visitor
                $checkIns = $entityManager->getRepository(CheckIns::class)->findBy(['visitor' => $visitor]);
                foreach ($checkIns as $checkIn) {
                    $entityManager->remove($checkIn);
                }

                $qrcode = $entityManager->getRepository(QRCodes::class)->findBy(['visitor' => $visitor]);
                foreach ($qrcode as $qrcodes) {
                    $entityManager->remove($qrcodes);
                }

                // Delete requests for this visitor
                $requests = $entityManager->getRepository(Requests::class)->findBy(['visitor' => $visitor]);
                foreach ($requests as $request) {
                    $entityManager->remove($request);
                }

                // Now delete the visitor
                $entityManager->remove($visitor);
            }

            // Delete users
            foreach ($users as $user) {
                $entityManager->remove($user);
            }

            // Delete QR users
            foreach ($qrUsers as $qrUser) {
                $entityManager->remove($qrUser);
            }

            // Delete events
            foreach ($events as $event) {
                $entityManager->remove($event);
            }

            // Finally, delete the company
            $entityManager->remove($company);
            $entityManager->flush();

            $entityManager->getConnection()->commit();

            return $this->json(['message' => 'Company and all related records deleted successfully'], 200);
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();
            return $this->json(['error' => 'Error deleting company: ' . $e->getMessage()], 500);
        }
    }
}

    

