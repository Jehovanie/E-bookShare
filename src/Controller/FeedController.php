<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Comment;
use App\Entity\Like;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
#[Route('/feed')]
final class FeedController extends AbstractController
{
    #[Route('', name: 'app_feed', methods: ['GET'])]
    public function index(BookRepository $bookRepository, Request $request): Response
    {
        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;
        $total   = $bookRepository->countAll();
        $books   = $bookRepository->findFeed($page, $perPage);

        $form = $this->createForm(BookType::class, new Book(), [
            'action' => $this->generateUrl('app_feed_publish'),
            'method' => 'POST',
        ]);

        return $this->render('feed/index.html.twig', [
            'books'    => $books,
            'form'     => $form,
            'page'     => $page,
            'perPage'  => $perPage,
            'total'    => $total,
            'lastPage' => (int) ceil($total / $perPage),
        ]);
    }

    #[Route('/publish', name: 'app_feed_publish', methods: ['POST'])]
    public function publish(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
            $file = $form->get('bookFile')->getData();

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename     = $slugger->slug($originalFilename);
                $newFilename      = $safeFilename . '-' . uniqid() . '.pdf';

                try {
                    $file->move(
                        $this->getParameter('books_directory'),
                        $newFilename
                    );
                    $book->setFilepath('books/' . $newFilename);
                } catch (FileException) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du fichier.');
                }
            }

            $book->setOwner($this->getUser());
            $book->setUploadetat(new \DateTimeImmutable());
            $em->persist($book);
            $em->flush();

            $this->addFlash('success', '📚 Votre livre a été publié avec succès !');
        } else {
            // Re-render feed with form errors
            /** @var \App\Repository\BookRepository $bookRepository */
            $bookRepository = $em->getRepository(Book::class);
            $books  = $bookRepository->findFeed(1, 10);
            $total  = $bookRepository->countAll();

            return $this->render('feed/index.html.twig', [
                'books'    => $books,
                'form'     => $form,
                'page'     => 1,
                'perPage'  => 10,
                'total'    => $total,
                'lastPage' => (int) ceil($total / 10),
            ]);
        }

        return $this->redirectToRoute('app_feed');
    }

    #[Route('/like/{id}', name: 'app_feed_like', methods: ['POST'])]
    public function like(
        Book $book,
        LikeRepository $likeRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user     = $this->getUser();
        $existing = $likeRepository->findOneByOwnerAndBook($user, $book);

        if ($existing) {
            $em->remove($existing);
            $em->flush();
            $liked = false;
        } else {
            $like = new Like();
            $like->setOwner($user);
            $like->setBook($book);
            $em->persist($like);
            $em->flush();
            $liked = true;
        }

        $count = $likeRepository->count(['book' => $book]);

        return $this->json(['liked' => $liked, 'count' => $count]);
    }

    #[Route('/comment/{id}', name: 'app_feed_comment', methods: ['POST'])]
    public function comment(
        Book $book,
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        $content = trim((string) $request->request->get('content', ''));

        if ($content === '') {
            return $this->json(['error' => 'Le commentaire ne peut pas être vide.'], 400);
        }

        if (mb_strlen($content) > 500) {
            return $this->json(['error' => 'Commentaire trop long (500 caractères max).'], 400);
        }

        $comment = new Comment();
        $comment->setContent(mb_substr($content, 0, 500));
        $comment->setCreatedat(new \DateTimeImmutable());
        $comment->setAuthor($this->getUser());
        $comment->setBook($book);

        $em->persist($comment);
        $em->flush();

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->json([
            'id'        => $comment->getId(),
            'content'   => $comment->getContent(),
            'createdat' => $comment->getCreatedat()->format('d M Y H:i'),
            'author'    => [
                'firstname' => $user->getFirstname(),
                'lastname'  => $user->getLastname(),
                'pseudo'    => $user->getPseudo(),
            ],
        ], 201);
    }
}
