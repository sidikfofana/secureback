<?php

namespace App\Repository;

use App\Entity\UserCheckIn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserCheckInRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCheckIn::class);
    }

    // Example: Find check-ins by user ID
    // public function findByUserId(int $userId)
    // {
    //     return $this->createQueryBuilder('u')
    //         ->where('u.userId = :userId')
    //         ->setParameter('userId', $userId)
    //         ->getQuery()
    //         ->getResult();
    // }
}
