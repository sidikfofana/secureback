<?php

namespace App\Controller;

use App\Repository\QRCodesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class CheckUidnController extends AbstractController
{
    /**
     * Class constructor.
     */
    public function __construct(private QRCodesRepository $qrCodeRepo)
    {}

    public function __invoke(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $qr = $this->qrCodeRepo->findOneBy(["uidn"=> $data['uidn']]);
        
    }
}
