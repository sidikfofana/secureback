<?php

namespace App\Controller;

use App\Entity\QRUser;
use App\Entity\User;
use App\Entity\Company;
use App\Helpers\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use App\Services\FileUploader;


class CreateQRController extends AbstractController
{

    public function __construct(
        private Helpers $Helpers,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private FileUploader $fileUploader,
    ) {
        // $this->Helpers = $Helpers;
    }  


    #[Route('/api/create-qr', name: 'app_create_q_r')]
    public function __invoke(Request $request): Response
    {
        $data = $request->request->all();
        $file = $request->files->get('user_document');
        //dd($data["company_id"]);

        // Fetch the company entity from the database
        $company = $this->em->getRepository(Company::class)->find($data["company_id"]);

        if (!$company) {
            return $this->json(["message" => "Company not found"], 404);
        }

        // Check if the email already exists in the QRUser table
        $qr = $this->em->getRepository(QRUser::class)->findOneBy(['email' => $data['email']]);

        if ($qr) {
            $qr->setDateExp($data['dateExp']); 
            $uidn = $qr->getUidn();
            $qr->setUpdatedAt(new \DateTimeImmutable());
        } else {
            $qr = new QRUser();
            $qr->setEmail($data['email']);
            $qr->setFirstName($data['firstname']); 
            $qr->setLastName($data['lastname']);
            $qr->setContact($data['contact']);
            $qr->setTitle($data['title']);
            $qr->setDateExp($data['dateExp']);
            $qr->setType($data["type"]);
            $qr->setCompany($company);

            $uidn = uniqid();
            $qr->setUidn($uidn);
            $qr->setCode(
                $this->Helpers->generateEncryptQR($data['type'], $data, $uidn)
            );
            $qr->setCreatedAt(new \DateTimeImmutable());
            if ($file) {
                $uploadedFilePath = $this->fileUploader->upload($file, "user_document");
                $qr->setUserImage($uploadedFilePath);
            }
        }

        $this->em->persist($qr);
        $this->em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'QR code updated successfully',
            'data' => ["uidn" => $qr->getUidn()]
        ], Response::HTTP_OK);
    }

    // public function __invoke(Request $request): Response
    // {
    //     $data = json_decode($request->getContent(), true);
    
    //     // Check if the user exists in the User table
    //     // $user = $this->em
    //     //              ->getRepository(User::class)
    //     //              ->findOneBy(['email' => $data['email']]);
    
    //     // if ($user) {
    //     //     $userId = $user->getId(); 
    //     //     $companyId = $user->getCompany(); 
    //     // } else {
    //     //     return $this->json([
    //     //         "message" => "Le mail n'existe pas"
    //     //     ], 404);
    //     // }
    //     $company = $this->em->getRepository(Company::class)->find($data["company_id"]);

    //     if (!$company) {
    //         return $this->json(["message" => "Company not found"], 404);
    //     }
        
    //     // Set the company in QRUser
       
    //     // Check if the email already exists in the QRUser table
    //     $qr = $this->em
    //         ->getRepository(QRUser::class)
    //         ->findOneBy(['email' => $data['email']]);
    
    //     if ($qr) {
    //         $qr->setDateExp($data['dateExp']); 
    //         $uidn = $qr->getUidn();
    //         $qr->setUpdatedAt(new \DateTimeImmutable());
    //     } else {
    //         // Create new QRUser entry if not found
    //         $qr = new QRUser();
    //         $qr->setEmail($data['email']);
    //         $qr->setFirstName($data['firstname']); 
    //         $qr->setLastName($data['lastname']);
    //         $qr->setContact($data['contact']);
    //         $qr->setTitle($data['title']);
    //         $qr->setDateExp($data['dateExp']);
    //         $qr->setType($data["type"]);
    //         $qr->setCompanyId($company);
    
    //         $uidn = uniqid();
    //         $qr->setUidn($uidn);
    //         $qr->setCode(
    //             $this->Helpers->generateEncryptQR($data['type'], $data, $uidn)
    //         );
    //         $qr->setCreatedAt(new \DateTimeImmutable());
    
    //         // If the user has a company_id, set it in QRUser
    //         // if (!empty($companyId)) {
    //         //     $qr->setCompany($companyId);
    //         // }
    //     }
    
    //     // Persist and flush changes
    //     $this->em->persist($qr);
    //     $this->em->flush();
    
    //     // Generate encrypted QR code only for new entries
    //     if ($qr->getUidn()) {
    //         $this->Helpers->generateEncryptQR($data['type'], $data, $uidn);
    //     }
    
    //     return new JsonResponse([
    //         'status' => 'success',
    //         'message' => 'QR code updated successfully',
    //         'data' => [
    //             "uidn" => $qr->getUidn()
    //         ]
    //     ], Response::HTTP_OK);
    // }
    


    private function sendEmail(MailerInterface $mailer, $to): Response
    {
        // Créez l'email
        $email = (new Email())
            // ->from('your_email@example.com')
            ->from('noreply@express54.org')
            ->to($to)
            ->subject('Secure Check - QRCode')
            ->text('Votre QRcode')
            ->html('<p> Cher client votre QR est en PJ</p>');

        try {
            $mailer->send($email);
            return new Response('Email sent successfully');
        } catch (\Exception $e) {
            return new Response('Failed to send email: ' . $e->getMessage());
        }
    }

    // #[Route('/api/qruser/{id}', name: 'qruser', methods: ['GET', 'PUT'])]
    // public function qrUser($id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    // {
    //     $user = $entityManager->getRepository(QrUser::class)->find($id);
    
    //     if (!$user) {
    //         return new JsonResponse(['message' => 'User not found'], 404);
    //     }
    
    //     if ($request->isMethod('GET')) {
    //         return new JsonResponse([
    //             'id' => $user->getId(),
    //             'firstname' => $user->getFirstname(),
    //             'lastname' => $user->getLastname(),
    //             'email' => $user->getEmail(),
    //             'contact' => $user->getContact(),
    //             'dateExp' => $user->getDateExp(),
    //         ]);
    //     }
    
    //     if ($request->isMethod('PUT')) {
    //         $data = json_decode($request->getContent(), true);
    //         if (isset($data['dateExp'])) {
    //             $user->setDateExp($data['dateExp']);
    //         }
    
    //         $entityManager->persist($user);
    //         $entityManager->flush();
    
    //         return new JsonResponse(['message' => 'Expiration date updated successfully'], 200);
    //     }
    
    //     return new JsonResponse(['message' => 'Method Not Allowed'], 405);
    // }

    #[Route('/api/qruser/{id}', name: 'user-qr-code', methods: ['GET', 'POST'])]
    public function qrUser($id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(QRUser::class)->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        // ✅ --- GET : retourner toutes les infos identiques à ton controller show() ---
        if ($request->isMethod('GET')) {
            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'uidn' => $user->getUidn(),
                    'type' => $user->getType(),
                    'dateExp' => $user->getDateExp(),
                    'firstname' => $user->getFirstName(),
                    'lastname' => $user->getLastName(),
                    'contact' => $user->getContact(),
                    'title' => $user->getTitle(),
                    'code' => $user->getCode(),
                    'isUsed' => $user->isUsed(),
                    'user_image' => $user->getUserImage(),

                    // ✅ relations
                    'company' => $user->getCompany()?->getName(),
                    'companyId' => $user->getCompany()?->getId(),

                    // ✅ dates
                    'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
                ]
            ]);
        }

        // ✅ --- PUT : mise à jour (mode édition) ---
        if ($request->isMethod('POST')) {

            // ✅ Ton frontend envoie du FormData → on récupère via $request->request et $request->files
            $email      = $request->request->get('email');
            $type       = $request->request->get('type');
            $firstname  = $request->request->get('firstname');
            $lastname   = $request->request->get('lastname');
            $contact    = $request->request->get('contact');
            $title      = $request->request->get('title');
            $dateExp    = $request->request->get('dateExp');
            $company_id = $request->request->get('company_id');

            $userDocument = $request->files->get('user_document');

            // ✅ Mise à jour des champs envoyés
            if ($email)      $user->setEmail($email);
            if ($type)       $user->setType($type);
            if ($firstname)  $user->setFirstName($firstname);
            if ($lastname)   $user->setLastName($lastname);
            if ($contact)    $user->setContact($contact);
            if ($title)      $user->setTitle($title);
            if ($dateExp)    $user->setDateExp($dateExp);
            //var_dump($dateExp);


            // ✅ Mise à jour de la compagnie
            if ($company_id) {
                $company = $entityManager->getRepository(Company::class)->find($company_id);
                if ($company) {
                    $user->setCompany($company);
                }
            }

            // ✅ Upload du fichier si présent
            if ($userDocument) {
                $filename = uniqid().'_'.$userDocument->getClientOriginalName();
                $userDocument->move('uploads/qr_users', $filename);
                $user->setUserImage($filename);
            }

            $user->setUpdatedAt(new \DateTime());

            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'QR User updated successfully'
            ]);
        }

        return new JsonResponse(['message' => 'Method Not Allowed'], 405);
    }
       
    #[Route('/api/test-token', name: 'api_test_token', methods: ['GET'])]
    public function testToken(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'No token provided'], 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);
        return  new JsonResponse(['token' => $token]);

        // Replace this with real token validation logic
        $expectedToken = '123456'; // Hardcoded test token

        if ($token !== $expectedToken) {
            return new JsonResponse(['error' => 'Invalid token'], 403);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Token is valid!',
            'token_received' => $token,
        ]);
    }
    
    #[Route('/api/qruser-list', name: 'qrUserList', methods: ['GET'])]
    public function qrUserList(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(QrUser::class)->findBy([], ['created_at' => 'DESC']);

        return $this->json($users, 200, [], ['groups' => 'qruser']);
    }
}
