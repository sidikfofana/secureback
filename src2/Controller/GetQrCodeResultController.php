<?php

namespace App\Controller;

use App\Entity\CheckIns;
use App\Repository\CheckInsRepository;
use App\Repository\QRCodesRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetQrCodeResultController extends AbstractController
{

    public function __construct(
        private QRCodesRepository $qRCodesRepo,
        private EntityManagerInterface $em,
        private CheckInsRepository $checkInsRepo
    ) {}

    #[Route('/get-qr-data/{uidn}')]
    public function getQrResult($uidn, Request $request)
    {
        $isManual = $request->query->get('type');

        $qr = $this->qRCodesRepo->findOneBy(['uidn' => $uidn]);

        if (!empty($qr)) {
            if ($qr->isUsed()) {
                return $this->json(
                    [
                        'status' => 'success',
                        'message' => "Déja utilisé"
                    ],
                    Response::HTTP_OK
                );
            }
        } else {
            return $this->json(
                [
                    'status' => 'success',
                    'message' => "Non Valide"
                ],
                Response::HTTP_OK
            );
        }


        $currentDateTime = new DateTime();
        if ($currentDateTime > $qr->getExpirationDate()) {
            return $this->json(
                [
                    'status' => 'error',
                    'message' => "Le QR code a expiré"
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $isAlreadyCheckIn = $this->checkInsRepo->findBy(['qr_code' => $qr]);
        if (sizeof($isAlreadyCheckIn) > 0) {
            $this->updateCheckIn($qr);

            if ($isManual) {

                return $this->json(
                    [
                        'status' => 'success',
                        'message' => "CheckOut éffectué"
                    ],
                    Response::HTTP_OK
                );
            }

            $url = $this->getParameter('domain_front') . '/success-checkout/' . $uidn;
            return $this->redirect($url);
        }

        $this->saveCheckIn($qr);

        if ($isManual) {

            return $this->json(
                [
                    'status' => 'success',
                    'message' => "CheckIn éffectué"
                ],
                Response::HTTP_OK
            );
        }
        $url = $this->getParameter('domain_front') . '/success-checkin/' . $uidn;

        return $this->redirect($url);
    }

    public function saveCheckIn($qr)
    {
        $checkIn = new CheckIns();
        $checkIn->setCheckInTime(new DateTimeImmutable());
        $checkIn->setVisitor($qr->getVisitor());
        $checkIn->setQrCode($qr);
        $checkIn->setCreatedAt(new DateTimeImmutable());

        $qr->addCheckIn($checkIn);
        $this->em->persist($checkIn);
        $this->em->persist($qr);
        $this->em->flush();
    }

    /*public function updateCheckIn($qr)
    {
        $checkIn = $this->checkInsRepo->findOneBy(['qr_code' => $qr]);
        $checkIn->setCheckOutTime(new DateTimeImmutable());
        $qr->addCheckIn($checkIn);
        $this->em->persist($checkIn);

        $qr->setUsed(true);
        $this->em->persist($qr);
        $this->em->flush();
    }*/
    public function updateCheckIn($qr)
    {
        $checkIn = $this->checkInsRepo->findOneBy(['qr_code' => $qr]);

        if (!$checkIn) {
            throw new \Exception('Check-in not found for the provided QR code.');
        }

        if ($qr->getVisitor()->getVisitorType() === 1) {
            if ($checkIn->getCheckOutTime() !== null) {
                $checkIn->setCheckInTime(new DateTimeImmutable());  
                $checkIn->setCheckOutTime(null); 
            } else {
                $checkIn->setCheckOutTime(new DateTimeImmutable());
            }
        } else {
            if ($checkIn->getCheckOutTime() === null) {
                $checkIn->setCheckOutTime(new DateTimeImmutable());
            } else {
                throw new \Exception('Temporary QR code has already been checked out.');
            }

            $qr->setUsed(true);  
            $this->em->persist($qr);
        }

        $this->em->persist($checkIn); 
        $this->em->flush(); 
    }
}
