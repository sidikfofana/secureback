<?php

namespace App\Services;

use App\Entity\Company;
use App\Entity\CompanyType;
use App\Repository\CompanyRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CompanyService extends AbstractController
{
    private $entityManager;
    private $validator;
    private $resourceName = 'Entreprise';

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        private SluggerInterface $slugger,
        private CompanyRepository $companyRepository,
        private FileUploader $fileUploader,

    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function add($data, $file)
    {
        $company = new Company();
        $company->setName(name: $data['name'])
            ->setDescription($data["description"])
            ->generateSlug($this->slugger)
            ->setCreatedAt(new DateTimeImmutable())
        ;

        $errors = $this->validator->validate($company);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return new JsonResponse([
                'errors' => $errorMessages
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // dd($file);
        $data['logo'] = $this->fileUploader->upload($file, "logo");
        $company->setLogo($data['logo']);

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $this->json(
            [
                'message' => 'Création effectuée',
                'data' => $company
            ],
            Response::HTTP_OK
        );
    }

    public function show($slug)
    {
        if (is_numeric($slug)) {
            $company = $this->companyRepository->find($slug);
        } else {
            $company = $this->companyRepository->findOneBy(['slug' => $slug]);
        }


        if (!$company) {
            return new JsonResponse([
                'message' => "$this->resourceName introuvable"
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json(data: [
            "message" => "Entreprise",
            "data" => $company
        ], status: 200, headers: [], context: ['groups' => 'company']);
    }

    public function update($data, $company)
    {
        if (!$company) {
            return new JsonResponse([
                'message' => "$this->resourceName introuvable"
            ], Response::HTTP_NOT_FOUND);
        }

        $company->setName($data['name'])->setDescription($data['description']);
        $company->generateSlug(
            $this->slugger
        );

        $this->entityManager->flush();

        return $this->json(data: [
            "message" => "Mise à jour effectuée",
            "data" => $company
        ], status: 200, headers: [], context: ['groups' => 'company']);
    }

    public function delete($company)
    {
        if (!$company) {
            return new JsonResponse([
                'message' => "$this->resourceName introuvable"
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($company);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Suppression effectuée'
        ], Response::HTTP_NO_CONTENT);
    }

    public function all()
    {
        $companies = $this->entityManager->getRepository(Company::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->json(
            [
                'data' => $companies
            ],
            Response::HTTP_OK,
            [],
            ["groups" => 'company']
        );
    }
}
