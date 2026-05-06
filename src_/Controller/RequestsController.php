<?php

namespace App\Controller;

use App\Entity\QRCodes;
use App\Entity\Requests;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\VisitorsRepository;
use App\Repository\UserRepository;
use App\Repository\RequestsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use App\Helpers\Helpers;

class RequestsController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;
    private $serializer;
    private $validator;
    private $Helpers;
    private $visitorsRepository;
    private $userRepository;
    private $requestRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        Helpers $Helpers,
        VisitorsRepository $visitorsRepository,
        UserRepository $userRepository,
        RequestsRepository $requestRepository,
        private Helpers $helpers
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->Helpers = $Helpers;
        $this->visitorsRepository = $visitorsRepository;
        $this->userRepository = $userRepository;
        $this->requestRepository = $requestRepository;
    }
    /**
     * @return Response
     **/
    #[Route('/api/requests/list', name: 'app_requests', methods: ['GET'])]
    public function visitorsList(EntityManagerInterface $entityManager): Response
    {
        $datas = $entityManager->getRepository(Requests::class)->findBy([],
            ["created_at" => "DESC"]);
        return $this->json($datas, 200, [], [
            'groups' => 'request'
        ]);
    }

    /**  
     * Enregistrement d'une demande de visite
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/requests/create', name: 'create_request', methods: ['POST'])]
    public function createRequest(Request $request): JsonResponse
    {

        //dd(json_decode($request->getContent(),true));
        try {

            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            // Define required fields
            $requiredFields = ['host'];

            // Validate required fields using the helper function
            $missingFields = $this->Helpers->validateRequiredFields($data, $requiredFields);
            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }

            $user = null;
            if (!empty($data['user_id'])) {
                $user = $this->userRepository->find($data['user_id']);

                // /dd($user);
                if (!$user) {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'User not found'
                    ], Response::HTTP_NOT_FOUND);
                }
            }

            $visiteur = null;
            if (!empty($data['visitor_id'])) {
                $visiteur = $this->visitorsRepository->find($data['visitor_id']);
                if (!$visiteur) {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Visitor not found'
                    ], Response::HTTP_NOT_FOUND);
                }
            }

            $request_datas = new Requests();

            if ($user) {
                $request_datas->setUser($user);
            }

            if ($visiteur) {
                $request_datas->setVisitor($visiteur);
            }

            $request_datas->setUser($user);
            $request_datas->setVisitor($visiteur);
            $request_datas->setHost($data['host']);
            $request_datas->setRequestDate(new \DateTime());
            $request_datas->setCreatedAt(new \DateTimeImmutable());

            // Save the visitor entity
            $this->entityManager->persist($request_datas);
            $this->entityManager->flush();

            // Validate the user entity
            $errors = $this->validator->validate($request_datas);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;

                return $this->json([
                    'status' => 'error',
                    'message' => $errorsString,
                ], Response::HTTP_BAD_REQUEST);
            }

            // dd($user ?? "OK");

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Request submitted successfully',
                'data' => [
                    "id" => $request_datas->getId()
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

    #[Route('/api/requests/update/{id}', name: 'update_request', methods: ['PUT'])]
    public function updaterequest(Request $request, $id): JsonResponse
    {

        try {

            $data = json_decode($request->getContent(), true);
            $request_datas = $this->requestRepository->find($id);

            if (!$request_datas) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Request not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Update requests details
            $request_datas->setResponseDate(new \DateTime());
            $request_datas->setUpdatedAt(new \DateTimeImmutable());
            $request_datas->setStatus(1);
            $request_datas->setConfirmed(1);

            $uidn = uniqid();
            // QR Code generation
            $qrCode = new QRCodes();
            $qrCode->setVisitor($request_datas->getVisitor());
            // dd($request_datas->getVisitor()->getId());
            // Assuming you have a visitor relation
            $qrCode->setCode(
                $this->helpers->generateEncrypt(
                    $request_datas->getVisitor()->getId(),
                    $uidn,
                    $request_datas->getHost()
                )
            ); // Assuming qrCodeService generates a QR code string
            $qrCode->setUidn($uidn); // Example unique identifier generation
            $qrCode->setType('Temporaire'); // Or 'Permanent' based on logic
            $qrCode->setExpirationDate(new \DateTime('+1 day')); // Set expiration date if applicable
            $qrCode->setStatus(1);
            $qrCode->setCreatedAt(new \DateTimeImmutable());
            $qrCode->setUpdatedAt(new \DateTimeImmutable());

            // Save updated visitor entity
            $this->entityManager->persist($request_datas);
            $this->entityManager->persist($qrCode);
            $this->entityManager->flush();

            $dataEmail = [
                "uidn" => $uidn
            ];

            $qrCodeUrl = "http://192.168.1.3:9999/qrcode/qrcode-$uidn.png";
            
            $this->helpers->sendEmail(
                $request_datas->getVisitor()->getEmail(),
                "Secure Check - QRCode",
                "
                <html>
                    <body>
                        <p>Bonjour,</p>
                        <p>Veuillez trouver ci-dessous votre QR code :</p>
                        <p><a href=\"$qrCodeUrl\"> QR Code </a> </p>
                        <p> Votre UIDN : $uidn</p>
                        <p>Merci et bonne journée !</p>
                    </body>
                </html>",
                $dataEmail
            );

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Request and QR code updated successfully',
                'data' => [
                    "uidn" => $uidn
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
