<?php
// src/Repository/TransactionRepository.php
namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByUser(User $user, int $limit = 50): array
    {
        $compteIds = $user->getComptesBancaires()->map(fn($c) => $c->getId())->toArray();
        if (empty($compteIds)) return [];

        return $this->createQueryBuilder('t')
            ->leftJoin('t.compteSource', 'cs')
            ->leftJoin('t.compteDestinataire', 'cd')
            ->where('cs.id IN (:ids) OR cd.id IN (:ids)')
            ->setParameter('ids', $compteIds)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecentByUser(User $user, int $limit = 5): array
    {
        return $this->findByUser($user, $limit);
    }

    public function sumDepots(): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.montant)')
            ->where('t.type = :type')
            ->setParameter('type', Transaction::TYPE_DEPOT)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
