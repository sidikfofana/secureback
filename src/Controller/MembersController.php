<?php

namespace App\Controller;

use App\Entity\Members;
use App\Entity\User;
use App\Entity\Company;
use App\Helpers\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Services\FileUploader;
use App\Repository\MembersRepository;
use App\Repository\CompanyRepository;


final class MembersController extends AbstractController
{
    public function __construct(
        private Helpers $Helpers,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private FileUploader $fileUploader,
    ) {
        // $this->Helpers = $Helpers;
    }  

    #[Route('/api/create-member', name: 'app_create_member', methods: ['POST'])]
    public function createMemberCard(Request $request): Response
    {
        $data = $request->request->all();
        $file = $request->files->get('user_document');

        // Récupération de l'entité Company
        $company = $this->em->getRepository(Company::class)->find($data["company_id"]);

        if (!$company) {
            return $this->json(["message" => "Company not found"], 404);
        }

        // Recherche d’un membre existant par email
        $qr = $this->em->getRepository(Members::class)->findOneBy(['email' => $data['email']]);

        // Calcul de la date d’expiration (1 an après aujourd’hui)
        $dateExp = (new \DateTimeImmutable())->modify('+1 year')->format('Y-m-d');

        if ($qr) {
            // Membre existant : mise à jour
            $qr->setDateExp($dateExp);
            $qr->setUpdatedAt(new \DateTimeImmutable());
            $uidn = $qr->getUidn();
        } else {
            // Nouveau membre : création
            $qr = new Members();
            $uidn = uniqid();

            $qr->setUidn($uidn);
            $qr->setEmail($data['email']);
            $qr->setFirstName($data['firstname']);
            $qr->setLastName($data['lastname']);
            $qr->setContact($data['contact']);
            $qr->setTitle($data['title']);
            $qr->setDateExp($dateExp);
            $qr->setType("permanent");
            $qr->setCompany($company);
            $qr->setCreatedAt(new \DateTimeImmutable());

            // Génération du code QR avec données encodées
            $email = str_replace(['@', '.'], ['@', '. '], $data['email']);
            //$contact = wordwrap($data['contact'], 2, ' ', true);
            $contact = implode('-', str_split($data['contact'], 2));
            //dd($contact);
            $qrData = [
                'email' => $email,
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'contact' => "ind".$contact,
                'title' => $data['title'],
            ];
            //dd($qrData);
            $qr->setCode(
                $this->Helpers->generateMembersQR("permanent", $qrData, $uidn)
            );

            // Gestion de l’upload de la photo
            if ($file) {
                $uploadedFilePath = $this->fileUploader->upload($file, "user_document");
                $qr->setUserImage($uploadedFilePath);
            }
        }

        // Sauvegarde
        $this->em->persist($qr);
        $this->em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Member created successfully',
            'data' => ["uidn" => $qr->getUidn(), "photo" => $qr->getUserImage()],
        ], Response::HTTP_OK);
    }

    #[Route('/api/qrusermembre/{id}', name: 'qruser', methods: ['GET', 'PUT'])]
    public function qrUserMembre($id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(Members::class)->find($id);
    
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }
    
        if ($request->isMethod('GET')) {
            return new JsonResponse([
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'contact' => $user->getContact(),
                'dateExp' => $user->getDateExp(),
                'photo' => $user->getUserImage()
            ]);
        }
    
        if ($request->isMethod('PUT')) {
            $data = json_decode($request->getContent(), true);
            $dateExp = (new \DateTimeImmutable())->modify('+1 year')->format('Y-m-d');
            if (isset($dateExp)) {
                $user->setDateExp($dateExp);
            }
    
            $entityManager->persist($user);
            $entityManager->flush();
    
            return new JsonResponse(['message' => 'Expiration date updated successfully'], 200);
        }
    
        return new JsonResponse(['message' => 'Method Not Allowed'], 405);
    }

    #[Route('/api/qrmembre-list', name: 'qrMmebreList', methods: ['GET'])]
    public function qrUserList(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(Members::class)->findBy([], ['created_at' => 'DESC']);

        return $this->json($users, 200, [], ['groups' => 'members']);
    }

    #[Route('/api/company/{slug}/members', name: 'company_members_by_slug', methods: ['GET'])]
    public function getCompanyMembersBySlug(
        string $slug,
        CompanyRepository $companyRepository,
        MembersRepository $membersRepository
    ): JsonResponse {
        $company = $companyRepository->findOneBy(['slug' => $slug]);

        if (!$company) {
            return $this->json(['error' => 'Company not found'], 404);
        }

        $members = $membersRepository->createQueryBuilder('m')
            ->where('m.company = :company')
            ->setParameter('company', $company)
            ->orderBy('m.created_at', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->json($members, 200, [], ['groups' => ['members']]);
    }
}
