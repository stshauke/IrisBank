<?php
// src/Repository/UserRepository.php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function searchClients(string $search): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :admin')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->orderBy('u.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('u.nom LIKE :s OR u.prenom LIKE :s OR u.email LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countClients(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles NOT LIKE :admin')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecentUsers(int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :admin')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
