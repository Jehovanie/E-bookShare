<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/notifications')]
final class NotificationController extends AbstractController
{
    #[Route('', name: 'app_notifications', methods: ['GET'])]
    public function index(NotificationRepository $repo): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user          = $this->getUser();
        $notifications = $repo->findForUser($user, 20);
        $unread        = $repo->countUnread($user);

        $items = array_map(fn ($n) => [
            'id'        => $n->getId(),
            'type'      => $n->getType(),
            'isRead'    => $n->isRead(),
            'createdAt' => $n->getCreatedAt()->format('d M Y H:i'),
            'sender'    => [
                'firstname' => $n->getSender()->getFirstname(),
                'lastname'  => $n->getSender()->getLastname(),
                'pseudo'    => $n->getSender()->getPseudo(),
            ],
            'book' => [
                'id'    => $n->getBook()->getId(),
                'title' => $n->getBook()->getTitle(),
            ],
        ], $notifications);

        return $this->json(['items' => $items, 'unread' => $unread]);
    }

    #[Route('/mark-read', name: 'app_notifications_mark_read', methods: ['POST'])]
    public function markRead(NotificationRepository $repo): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $repo->markAllReadForUser($user);

        return $this->json(['ok' => true]);
    }
}
