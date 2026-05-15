<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CommentModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class CommentController
{
    public function __construct(
        private Environment $twig,
        private CommentModel $commentModel,
        private string $basePath,
    ) {}

    //Create Comment
    public function create(Request $request, Response $response, array $args): Response
    {
        $artId = (int) $args['id'];
        $data = $request->getParsedBody();

        $content = trim($data['content'] ?? '');

        if ($content === '') {
            $response->getBody()->write("Comment cannot be empty");
            return $response->withStatus(400);
        }

        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response
                ->withHeader('Location', $this->basePath . '/login')
                ->withStatus(302);
        }

        $this->commentModel->create(
            $artId,
            (int) $user['id'],
            $content
        );

        return $response
            ->withHeader('Location', $this->basePath . "/art/$artId")
            ->withStatus(302);
    }

    //Update Comment
    public function update(Request $request, Response $response, array $args): Response
    {
        $commentId = (int) $args['id'];
        $data = $request->getParsedBody();

        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response
                ->withHeader('Location', $this->basePath . '/login')
                ->withStatus(302);
        }

        $content = trim($data['content'] ?? '');

        if (!$this->commentModel->isOwner($commentId, (int) $user['id'])) {
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(403);
        }

        $this->commentModel->updateContent($commentId, $content);

        return $response
            ->withHeader('Location', $this->basePath . '/art/' . $data['art_id'])
            ->withStatus(302);
    }

    //Delete Comment
    public function delete(Request $request, Response $response, array $args): Response
    {
        $commentId = (int) $args['id'];

        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response
                ->withHeader('Location', $this->basePath . '/login')
                ->withStatus(302);
        }

        $comment = $this->commentModel->findById($commentId);

        if (!$comment || !$comment->id) {
            return $response->withStatus(404);
        }

        $userId = (int) $user['id'];

        $isCommentOwner = ((int)$comment->user_id === $userId);
        $isArtOwner = $this->commentModel->isArtOwner((int)$comment->art_id, $userId);

        if (!$isCommentOwner && !$isArtOwner) {
            return $response->withStatus(403);
        }

        $this->commentModel->deleteById($commentId);

        return $response
            ->withHeader('Location', $this->basePath . '/art/' . $comment->art_id)
            ->withStatus(302);
    }
}