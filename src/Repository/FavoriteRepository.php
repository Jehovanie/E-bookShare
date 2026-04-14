<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Favorite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findOneByOwnerAndBook(User $owner, Book $book): ?Favorite
    {
        return $this->findOneBy(['owner' => $owner, 'book' => $book]);
    }

    /**
     * @return Favorite[]
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.book', 'b')
            ->addSelect('b')
            ->innerJoin('b.owner', 'o')
            ->addSelect('o')
            ->andWhere('f.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
