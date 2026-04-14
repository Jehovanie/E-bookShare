<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function isPseudoTaken(string $pseudo): bool
    {
        return (bool) $this->createQueryBuilder('u')
            ->select('1')
            ->where('LOWER(u.pseudo) = LOWER(:pseudo)')
            ->setParameter('pseudo', $pseudo)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns existing pseudos that start with the same base (for suggestions).
     *
     * @return string[]
     */
    public function findTakenPseudosLike(string $pseudo): array
    {
        $results = $this->createQueryBuilder('u')
            ->select('u.pseudo')
            ->where('LOWER(u.pseudo) LIKE LOWER(:pattern)')
            ->setParameter('pattern', $pseudo . '%')
            ->setMaxResults(20)
            ->getQuery()
            ->getArrayResult();

        return array_column($results, 'pseudo');
    }

    /** @return User[] */
    public function findAdmins(): array
    {
        $ids = $this->getEntityManager()
            ->getConnection()
            ->executeQuery('SELECT id FROM "user" WHERE roles::text LIKE :role', ['role' => '%ROLE_ADMIN%'])
            ->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /** @return User[] */
    public function findForAdmin(string $search = '', int $page = 1, int $perPage = 20): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($search !== '') {
            $qb->where('LOWER(u.firstname) LIKE LOWER(:s) OR LOWER(u.lastname) LIKE LOWER(:s) OR LOWER(u.pseudo) LIKE LOWER(:s) OR LOWER(u.email) LIKE LOWER(:s)')
               ->setParameter('s', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countForAdmin(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('u')->select('COUNT(u.id)');

        if ($search !== '') {
            $qb->where('LOWER(u.firstname) LIKE LOWER(:s) OR LOWER(u.lastname) LIKE LOWER(:s) OR LOWER(u.pseudo) LIKE LOWER(:s) OR LOWER(u.email) LIKE LOWER(:s)')
               ->setParameter('s', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
