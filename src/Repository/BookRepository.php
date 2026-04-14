<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * Returns all books ordered by most recent, with owner, like count and comment count.
     *
     * @return Book[]
     */
    public function findFeed(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        return $this->createQueryBuilder('b')
            ->addSelect('owner')
            ->join('b.owner', 'owner')
            ->orderBy('b.uploadetat', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Book[] */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('b.uploadetat', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Book[] */
    public function findForAdmin(string $search = '', int $page = 1, int $perPage = 20): array
    {
        $qb = $this->createQueryBuilder('b')
            ->addSelect('owner')
            ->join('b.owner', 'owner')
            ->orderBy('b.uploadetat', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($search !== '') {
            $qb->where('LOWER(b.title) LIKE LOWER(:s) OR LOWER(owner.pseudo) LIKE LOWER(:s) OR LOWER(owner.firstname) LIKE LOWER(:s)')
               ->setParameter('s', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countForAdmin(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->join('b.owner', 'owner');

        if ($search !== '') {
            $qb->where('LOWER(b.title) LIKE LOWER(:s) OR LOWER(owner.pseudo) LIKE LOWER(:s) OR LOWER(owner.firstname) LIKE LOWER(:s)')
               ->setParameter('s', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
