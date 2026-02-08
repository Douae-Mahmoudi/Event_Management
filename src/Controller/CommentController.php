<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Service\CommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comment')]
class CommentController extends AbstractController
{
    public function __construct(
        private CommentService $commentService,
        private EventRepository $eventRepo,
        private UserRepository $userRepo
    ) {}

    #[Route('/add', name: 'app_comment_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        // On récupère les données JSON si elles existent
        $data = json_decode($request->getContent(), true) ?? [];
        
        $content = $data['content'] ?? $request->request->get('content');
        $eventId = $data['event_id'] ?? $request->request->get('event_id');
        $userId  = $data['userId']   ?? $request->request->get('userId');

        $event = $this->eventRepo->find($eventId);
        if (!$event) {
            return $this->json(['error' => 'Événement introuvable'], 404);
        }

        $user = $this->getUser() ?: ($userId ? $this->userRepo->find($userId) : null);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non identifié'], 403);
        }

        if (empty($content)) {
            return $this->json(['error' => 'Le contenu ne peut pas être vide'], 400);
        }

        $comment = $this->commentService->createComment($content, $event, $user);

        if ($request->headers->get('Content-Type') === 'application/json' || $request->isXmlHttpRequest()) {
            return $this->json(['status' => 'success', 'id' => $comment->getId()], 201);
        }

        $this->addFlash('success', 'Commentaire ajouté !');
        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/event/{id}', name: 'app_comment_list', methods: ['GET'])]
    public function list(Event $event): Response
    {
        $result = [];
        foreach ($event->getComments() as $comment) {
            $result[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'author' => $comment->getUser()->getEmail(),
                'date' => $comment->getCommentDate()?->format('Y-m-d H:i:s'),
            ];
        }
        return $this->json($result);
    }

    #[Route('/edit/{id}', name: 'app_comment_edit', methods: ['PUT', 'PATCH'])]
    public function edit(Comment $comment, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $newContent = $data['content'] ?? null;

        if (empty($newContent)) {
            return $this->json(['error' => 'Le contenu est vide'], 400);
        }

        $this->commentService->updateComment($comment, $newContent);
        return $this->json(['status' => 'success', 'message' => 'Mis à jour']);
    }

    #[Route('/delete/{id}', name: 'app_comment_delete', methods: ['DELETE'])]
    public function delete(Comment $comment): Response
    {
        $this->commentService->deleteComment($comment);
        return $this->json(['status' => 'success', 'message' => 'Supprimé']);
    }
}