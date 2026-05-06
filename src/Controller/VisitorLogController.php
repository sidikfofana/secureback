<?php

namespace App\Controller;

use App\Entity\CheckIns;
use App\Entity\User ;
use App\Repository\CheckInsRepository;
use App\Repository\QRCodesRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Helpers\Helpers;
use App\Entity\Company;
use App\Entity\Requests;
use App\Entity\Visitors;
use App\Entity\QRCodes;
use App\Entity\QRUser;

class VisitorLogController extends AbstractController
{
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

    #[Route('/api/visitorlog', name: 'api_visitorlog', methods: ['GET'])]
    public function getVisitorLog(EntityManagerInterface $entityManager): JsonResponse
    {
        // Retrieve all CheckIns entities sorted by created_at in descending order
        $datas = $entityManager->getRepository(CheckIns::class)->findBy([], ['created_at' => 'DESC']);
        // Format the data
        $data = [];
        foreach ($datas as $checkIn) {
            $data[] = [
                //'id' => $checkIn->getId(),
                'visitor_id' => $checkIn->getVisitor()->getId(),
                'visitor_email' => $checkIn->getVisitor()->getEmail(),
                'visitor_name' => $checkIn->getVisitor()->getFirstname(),
                'visitor_lastname' => $checkIn->getVisitor()->getLastname(),
                'image' => $checkIn->getVisitor()->getRequestImage(),
                //->getVisitor()->getId(),
                //'qr_code_id' => $checkIn->getQrCodeId(),
                'check_in_time' => $checkIn->getCheckInTime() ? $checkIn->getCheckInTime()->format('Y-m-d H:i:s') : null,
                'check_out_time' => $checkIn->getCheckOutTime() ? $checkIn->getCheckOutTime()->format('Y-m-d H:i:s') : null,
                'created_at' => $checkIn->getVisitor()->getCreatedAt() ? $checkIn->getVisitor()->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'updated_at' => $checkIn->getUpdatedAt() ? $checkIn->getUpdatedAt()->format('Y-m-d H:i:s') : null,
            ];
        }


        return new JsonResponse($data);

    }

    #[Route('/api/visitorLog/{companySlug}', name: 'visitor_log_comp', methods: ['GET'])]
    public function visitorsListByComp(EntityManagerInterface $entityManager, $companySlug): JsonResponse
    {
        $company = $entityManager->getRepository(Company::class)
            ->findOneBy(['slug' => $companySlug]);
        
        if (!$company) {
            return new JsonResponse(["error" => 'Company not found'], 404);
        }

        // Retrieve all CheckIns entities sorted by created_at in descending order
        $datas = $entityManager->getRepository(CheckIns::class)->findBy([], ['created_at' => 'DESC']);
        
        // Format the data
        $finalDatas = [];
        foreach ($datas as $checkIn) {
            if ($checkIn->getVisitor()?->getCompany()?->getSlug() === $company->getSlug()) {
                $finalDatas[] = [
                    'visitor_id' => $checkIn->getVisitor()->getId(),
                    'visitor_email' => $checkIn->getVisitor()->getEmail(),
                    'visitor_name' => $checkIn->getVisitor()->getFirstname(),
                    'visitor_lastname' => $checkIn->getVisitor()->getLastname(),
                    'image' => $checkIn->getVisitor()->getRequestImage(),
                    'check_in_time' => $checkIn->getCheckInTime() ? $checkIn->getCheckInTime()->format('Y-m-d H:i:s') : null,
                    'check_out_time' => $checkIn->getCheckOutTime() ? $checkIn->getCheckOutTime()->format('Y-m-d H:i:s') : null,
                    'created_at' => $checkIn->getVisitor()->getCreatedAt() ? $checkIn->getVisitor()->getCreatedAt()->format('Y-m-d H:i:s') : null,
                    'updated_at' => $checkIn->getUpdatedAt() ? $checkIn->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                ];
            }
        }

        return new JsonResponse($finalDatas);
    }

    #[Route('/api/visitorLog-checkIn/{uidn}', name: 'visitor_log_comp_checkIn', methods: ['GET'])]
    public function visitorsListByCheckInUser(EntityManagerInterface $entityManager, $uidn): JsonResponse
    {
        $qr_code = $entityManager->getRepository(QRCodes::class)
            ->findOneBy(['uidn' => $uidn]);
           
        
        if (!$qr_code) {
            return new JsonResponse(["error" => 'Qr code not found'], 404);
        }
        $finalDatas = [];
        $finalDatas = [
            'visitor_id' => $qr_code->getVisitor()->getId(),
            'visitor_email' => $qr_code->getVisitor()->getEmail(),
            'visitor_name' => $qr_code->getVisitor()->getFirstname(),
            'image' => $qr_code->getVisitor()->getRequestImage(),
        ];
        return new JsonResponse($finalDatas);
    }

    #[Route('/api/userLog-checkIn/{uidn}', name: 'user_log_comp_checkIn', methods: ['GET'])]
    public function usersListByCheckInUser(EntityManagerInterface $entityManager, $uidn): JsonResponse
    {
        $qr_code = $entityManager->getRepository(QRUser::class)
            ->findOneBy(['uidn' => $uidn]);
           
        
        if (!$qr_code) {
            return new JsonResponse(["error" => 'Qr code not found'], 404);
        }
        $finalDatas = [];
        $finalDatas = [
            'image' => $qr_code->getUserImage(),
        ];
        return new JsonResponse($finalDatas);
    }


    #[Route('/api/visitor/delete/{id}', name: 'delete_visitor', methods: ['DELETE'])]
    public function deleteVisitor(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->getConnection()->beginTransaction(); // Start transaction

        try {
            $visitor = $entityManager->getRepository(Visitors::class)->find($id);

            if (!$visitor) {
                return $this->json(['message' => 'Visitor not found'], 404);
            }

            // Delete associated records in requests, qrcodes, and check_ins
            $requests = $entityManager->getRepository(Requests::class)->findBy(['visitor' => $visitor]);
            $qrcodes = $entityManager->getRepository(QrCodes::class)->findBy(['visitor' => $visitor]);
            $checkIns = $entityManager->getRepository(CheckIns::class)->findBy(['visitor' => $visitor]);

            foreach ($requests as $request) {
                $entityManager->remove($request);
            }

            foreach ($qrcodes as $qrcode) {
                $entityManager->remove($qrcode);
            }

            foreach ($checkIns as $checkIn) {
                $entityManager->remove($checkIn);
            }

            // Delete visitor after removing related records
            $entityManager->remove($visitor);
            $entityManager->flush(); // Persist changes

            $entityManager->getConnection()->commit(); // Commit transaction

            return $this->json(['message' => 'Visitor and related records deleted successfully'], 200);
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack(); // Rollback in case of error
            return $this->json(['error' => 'Error deleting visitor: ' . $e->getMessage()], 500);
        }
    }

   

}
