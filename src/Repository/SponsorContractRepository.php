<?php

namespace App\Repository;

use App\Entity\SponsorContract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SponsorContract>
 */
class SponsorContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SponsorContract::class);
    }

    /**
     * Retourne les contrats qui expirent dans les $days prochains jours.
     *
     * @return SponsorContract[]
     */
    public function findExpiringWithinDays(int $days): array
    {
        $now   = new \DateTimeImmutable('today');
        $limit = $now->modify("+$days days");

        return $this->createQueryBuilder('c')
            ->leftJoin('c.sponsor', 's')
            ->addSelect('s')
            ->andWhere('c.expiresAt BETWEEN :now AND :limit')
            ->setParameter('now', $now)
            ->setParameter('limit', $limit)
            ->orderBy('c.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SponsorContract[]
     */
    public function findAllWithSponsor(): array
    {
        return $this->createQueryBuilder('sc')
            ->leftJoin('sc.sponsor', 's')
            ->addSelect('s')
            ->orderBy('sc.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SponsorContract[]
     */
    public function findRecentWithSponsor(int $limit = 5): array
    {
        return $this->createQueryBuilder('sc')
            ->leftJoin('sc.sponsor', 's')
            ->addSelect('s')
            ->orderBy('sc.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return SponsorContract[] Returns an array of SponsorContract objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SponsorContract
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
