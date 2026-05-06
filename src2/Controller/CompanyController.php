<?php

namespace App\Controller;

use App\Entity\Company;
use App\Services\CompanyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CompanyController extends AbstractController
{
    private $companyService;

    public function __construct(CompanyService $companyService,)
    {
        $this->companyService = $companyService;
    }

    #[Route('/api/company', name: 'company_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            $data = $request->request->all();
        }
        $file = $request->files->get('logo');

        return $this->companyService->add($data, $file);
    }

    #[Route('/api/company/{slug}', name: 'company_show', methods: ['GET'])]
    public function show($slug): JsonResponse
    {
        return $this->companyService->show($slug);
    }

    #[Route('/api/company/{id}', name: 'company_update', methods: ['PUT'])]
    public function update(Request $request, Company $company): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->companyService->update($data, $company);
    }

    #[Route('/company/{id}', name: 'company_delete', methods: ['DELETE'])]
    public function delete(Company $company): JsonResponse
    {
        return $this->companyService->delete($company);
    }

    #[Route('/api/companies', name: 'company_all', methods: ['GET'])]
    public function all(): JsonResponse
    {
        return $this->companyService->all();
    }
}
