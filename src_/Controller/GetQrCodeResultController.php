<?php

namespace App\Controller;

use App\Entity\CheckIns;
use App\Repository\CheckInsRepository;
use App\Repository\QRCodesRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function getQrResult($uidn)
    {

        $qr = $this->qRCodesRepo->findOneBy(['uidn' => $uidn]);
        if ($qr->isUsed()) {
            return $this->json(
                [
                    'status' => 'success',
                    'message' => "Déja utilisé"
                ],
                Response::HTTP_OK
            );
        }

        $currentDateTime = new DateTime();
        if ($currentDateTime > $qr->getExpirationDate()) {
            return $this->json(
                [
                    'status' => 'error',
                    'message' => "Le QR code est expiré"
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $isAlreadyCheckIn = $this->checkInsRepo->findBy(['qr_code' => $qr]);
        if (sizeof($isAlreadyCheckIn) > 0) {
            $this->updateCheckIn($qr);

            return $this->json(
                [
                    'status' => 'success',
                    'message' => "CheckOut éffectué"
                ],
                Response::HTTP_OK
            );
        }
        $this->saveCheckIn($qr);

        return $this->json(
            [
                'status' => 'success',
                'message' => "CheckIn éffectué"
            ],
            Response::HTTP_OK
        );
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

    public function updateCheckIn($qr)
    {
        $checkIn = $this->checkInsRepo->findOneBy(['qr_code' => $qr]);
        $checkIn->setCheckOutTime(new DateTimeImmutable());
        $qr->addCheckIn($checkIn);
        $this->em->persist($checkIn);

        $qr->setUsed(true);
        $this->em->persist($qr);
        $this->em->flush();
    }
}
