<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CommentService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}


    public function findAllPaginated(): array
    {
        return $this->entityManager->getRepository(Comment::class)->findBy([], ['id' => 'DESC']);
    }

    public function createComment(string $content, Event $event, User $user): Comment
    {
        $comment = new Comment();
        $comment->setContent($content);
        $comment->setEvent($event);
        $comment->setUser($user);
        
        // Sécurité : si la date n'est pas gérée dans l'entité
        if (method_exists($comment, 'setCommentDate')) {
            $comment->setCommentDate(new \DateTime('now', new \DateTimeZone('Africa/Casablanca')));

        }

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    public function updateComment(Comment $comment, string $newContent): void
    {
        $comment->setContent($newContent);
        $this->entityManager->flush();
    }

    public function deleteComment(Comment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }
}