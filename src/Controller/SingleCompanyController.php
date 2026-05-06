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

class SingleCompanyController extends AbstractController
{
    private $entityManager;
    private $validator;
    private $resourceName = 'Entreprise';
    private $companyRepository;
    public function __construct(CompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    #[Route('/api/singlecompanylist', name: 'single_company_list')]
    public function getsinglecompanylist(EntityManagerInterface $entityManager): JsonResponse
    {
        $url = $_GET["url"];
        $company = $this->companyRepository->findOneBy(['slug' => $url]);
        if (!$company) {
            return new JsonResponse([
                'message' => "$this->resourceName introuvable"
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json(data: [
            "message" => "Entreprise",
            "data" => $company
        ], status: 200, headers: [], context: ['groups' => 'company']);
		echo "Test"; die;
    }
}
