<?php
// ============================================================
// src/Repository/UserRepository.php
// ============================================================
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

    /**
     * Recherche avancée :
     *  - $search    : nom, prénom ou email
     *  - $iban      : numéro de compte (IBAN)
     *  - $nbComptes : filtre sur le nombre de comptes ('0', '1', '2', '3+')
     */
    public function searchClients(string $search = '', string $iban = '', string $nbComptes = ''): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.comptesBancaires', 'c')
            ->addSelect('c')
            // Exclure les admins
            ->where('u.roles NOT LIKE :admin')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->orderBy('u.createdAt', 'DESC');

        // Filtre nom / prénom / email
        if ($search !== '') {
            $qb->andWhere(
                'u.nom LIKE :s OR u.prenom LIKE :s OR u.email LIKE :s'
            )->setParameter('s', '%' . $search . '%');
        }

        // Filtre par IBAN
        if ($iban !== '') {
            $qb->andWhere('c.iban LIKE :iban')
               ->setParameter('iban', '%' . $iban . '%');
        }

        $results = $qb->getQuery()->getResult();

        // Filtre nb comptes (fait en PHP car COUNT dans DQL avec leftJoin est complexe)
        if ($nbComptes !== '') {
            $results = array_filter($results, function (User $u) use ($nbComptes) {
                $nb = $u->getComptesBancaires()->count();
                return match ($nbComptes) {
                    '0'  => $nb === 0,
                    '1'  => $nb === 1,
                    '2'  => $nb === 2,
                    '3+' => $nb >= 3,
                    default => true,
                };
            });
        }

        return array_values($results);
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
