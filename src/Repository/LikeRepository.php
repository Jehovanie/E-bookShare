<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Like;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Like>
 */
class LikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Like::class);
    }

    public function findOneByOwnerAndBook(User $owner, Book $book): ?Like
    {
        return $this->findOneBy(['owner' => $owner, 'book' => $book]);
    }
}
