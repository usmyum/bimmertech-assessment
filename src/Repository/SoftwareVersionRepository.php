<?php

namespace App\Repository;

use App\Entity\SoftwareVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SoftwareVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SoftwareVersion::class);
    }

    /**
     * Find a version by its alt version string (case-insensitive).
     * Returns all matches so the controller can apply hardware filtering.
     *
     * @return SoftwareVersion[]
     */
    public function findByVersionAlt(string $versionAlt): array
    {
        return $this->createQueryBuilder('s')
            ->where('LOWER(s.systemVersionAlt) = LOWER(:v)')
            ->setParameter('v', $versionAlt)
            ->getQuery()
            ->getResult();
    }
}
