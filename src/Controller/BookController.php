<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/my-books')]
final class BookController extends AbstractController
{
    #[Route('', name: 'app_my_books', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        $books = $bookRepository->findByOwner($this->getUser());

        return $this->render('book/my_books.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_book_delete', methods: ['POST'])]
    public function delete(Book $book, EntityManagerInterface $em): Response
    {
        // Only the owner can delete
        if ($book->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce livre.');
        }

        // Remove the physical file if it exists
        if ($book->getFilepath()) {
            $filepath = $this->getParameter('kernel.project_dir') . '/public/' . $book->getFilepath();
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
        }

        $em->remove($book);
        $em->flush();

        $this->addFlash('success', '🗑️ Le livre "' . $book->getTitle() . '" a été supprimé.');

        return $this->redirectToRoute('app_my_books');
    }
}
