<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\FollowModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FollowController
{
    public function __construct(
        private FollowModel $followModel,
        private string $basePath
    ) {}

    public function follow(Request $request, Response $response, array $args): Response
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }

        $this->followModel->follow((int)$user['id'], (int)$args['id']);

        return $response->withHeader('Location', $this->basePath . '/profile')->withStatus(302);
    }

    public function unfollow(Request $request, Response $response, array $args): Response
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }

        $this->followModel->unfollow((int)$user['id'], (int)$args['id']);

        return $response->withHeader('Location', $this->basePath . '/profile')->withStatus(302);
    }
}