<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;

class CommentModel
{
    private string $table = 'comment';

    public function findByArtId(int $artId): array
    {
        return array_values(
            R::find($this->table, 'art_id = ? ORDER BY created_at ASC', [$artId])
        );
    }

    public function create(int $artId, int $userId, string $content): int
    {
        $comment = R::dispense($this->table);

        $comment->art_id = $artId;
        $comment->user_id = $userId;
        $comment->content = $content;
        $comment->created_at = date('Y-m-d H:i:s');

        return R::store($comment);
    }

    public function findById(int $id): mixed
    {
        $comment = R::load($this->table, $id);

        return $comment->id ? $comment : null;
    }

    public function isOwner(int $commentId, int $userId): bool
    {
        $comment = R::load($this->table, $commentId);

        if (!$comment->id) {
            return false;
        }

        return (int) $comment->user_id === $userId;
    }

    public function isArtOwner(int $artId, int $userId): bool
    {
        $art = R::load('art', $artId);

        if (!$art->id) {
            return false;
        }

        return (int) $art->user_id === $userId;
    }

    public function updateComment(int $id, string $content): void
    {
        $comment = R::load($this->table, $id);

        if ($comment->id) {
            $comment->content = $content;
            $comment->updated_at = date('Y-m-d H:i:s');

            R::store($comment);
        }
    }

    public function deleteById(int $id): void
    {
        $comment = R::load($this->table, $id);

        if ($comment->id) {
            R::trash($comment);
        }
    }
}