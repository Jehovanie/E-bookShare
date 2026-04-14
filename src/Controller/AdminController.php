<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
final class AdminController extends AbstractController
{
    // ── Dashboard ────────────────────────────────────────────────
    #[Route('', name: 'app_admin', methods: ['GET'])]
    public function dashboard(
        UserRepository $userRepo,
        BookRepository $bookRepo,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'totalUsers'    => $userRepo->count([]),
            'totalBooks'    => $bookRepo->countAll(),
            'recentUsers'   => $userRepo->findForAdmin('', 1, 5),
            'recentBooks'   => $bookRepo->findForAdmin('', 1, 5),
        ]);
    }

    // ── Users list ───────────────────────────────────────────────
    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(Request $request, UserRepository $userRepo): Response
    {
        $search  = trim((string) $request->query->get('q', ''));
        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = 20;

        return $this->render('admin/users.html.twig', [
            'users'    => $userRepo->findForAdmin($search, $page, $perPage),
            'total'    => $userRepo->countForAdmin($search),
            'page'     => $page,
            'lastPage' => (int) ceil($userRepo->countForAdmin($search) / $perPage),
            'search'   => $search,
            'perPage'  => $perPage,
        ]);
    }

    // ── Toggle ROLE_ADMIN ────────────────────────────────────────
    #[Route('/users/{id}/toggle-admin', name: 'app_admin_user_toggle_admin', methods: ['POST'])]
    public function toggleAdmin(User $user, EntityManagerInterface $em): Response
    {
        // Prevent removing own admin role
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre rôle.');
            return $this->redirectToRoute('app_admin_users');
        }

        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $user->setRoles(array_values(array_filter($roles, fn ($r) => $r !== 'ROLE_ADMIN')));
            $this->addFlash('success', "@{$user->getPseudo()} n'est plus administrateur.");
        } else {
            $user->setRoles(array_unique([...$roles, 'ROLE_ADMIN']));
            $this->addFlash('success', "@{$user->getPseudo()} est maintenant administrateur.");
        }

        $em->flush();
        return $this->redirectToRoute('app_admin_users', ['q' => '', 'page' => 1]);
    }

    // ── Delete user ──────────────────────────────────────────────
    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $em): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_users');
        }

        $pseudo = $user->getPseudo();
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', "L'utilisateur @{$pseudo} a été supprimé.");
        return $this->redirectToRoute('app_admin_users');
    }

    // ── Reset password ───────────────────────────────────────────
    #[Route('/users/{id}/reset-password', name: 'app_admin_user_reset_password', methods: ['POST'])]
    public function resetPassword(
        User $user,
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        $newPassword = trim((string) $request->request->get('password', ''));

        if (strlen($newPassword) < 6) {
            $this->addFlash('error', 'Le mot de passe doit faire au moins 6 caractères.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setPassword($hasher->hashPassword($user, $newPassword));
        $em->flush();

        $this->addFlash('success', "Mot de passe de @{$user->getPseudo()} réinitialisé.");
        return $this->redirectToRoute('app_admin_users');
    }

    // ── Books list ───────────────────────────────────────────────
    #[Route('/books', name: 'app_admin_books', methods: ['GET'])]
    public function books(Request $request, BookRepository $bookRepo): Response
    {
        $search  = trim((string) $request->query->get('q', ''));
        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = 20;

        return $this->render('admin/books.html.twig', [
            'books'    => $bookRepo->findForAdmin($search, $page, $perPage),
            'total'    => $bookRepo->countForAdmin($search),
            'page'     => $page,
            'lastPage' => (int) ceil($bookRepo->countForAdmin($search) / $perPage),
            'search'   => $search,
            'perPage'  => $perPage,
        ]);
    }

    // ── Delete book ──────────────────────────────────────────────
    #[Route('/books/{id}/delete', name: 'app_admin_book_delete', methods: ['POST'])]
    public function deleteBook(Book $book, EntityManagerInterface $em): Response
    {
        if ($book->getFilepath()) {
            $filepath = $this->getParameter('kernel.project_dir') . '/public/' . $book->getFilepath();
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
        }

        $title = $book->getTitle();
        $em->remove($book);
        $em->flush();

        $this->addFlash('success', "Le livre \"{$title}\" a été supprimé.");
        return $this->redirectToRoute('app_admin_books');
    }
}
